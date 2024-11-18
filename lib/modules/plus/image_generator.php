<?php

namespace Podlove\Modules\Plus;

class ImageGenerator
{
    private $module;
    private $api;
    private $token;

    public function __construct($module, $api)
    {
        $this->module = $module;
        $this->api = $api;
        $this->token = $api->getToken();
    }

    public static function is_enabled()
    {
        return true;
    }

    public function init()
    {
        add_filter('podlove_ogp_image_data', [$this, 'override_open_graph_image_data']);
        add_filter('podlove_api_podcast_response', [$this, 'append_to_podcast_api']);

        // debug preview
        // add_action('admin_notices', function () {
        //     $image = \Podlove\Model\Podcast::get()->cover_image;

        //     $url = $this->get_open_graph_image_url($image, '#F3F4F6');
        //     echo '<img src="'.$url.'" style="width: 600px; margin-top: 10px" />';
        // });
    }

    public function override_open_graph_image_data($original_data)
    {
        $image = \Podlove\Model\Podcast::get()->cover_image;
        $url = $this->get_open_graph_image_url($image, '#F3F4F6');

        if (!$url) {
            return $original_data;
        }

        return [
            'property' => 'og:image',
            'content' => $url,
        ];
    }

    public function append_to_podcast_api($response)
    {
        $image = \Podlove\Model\Podcast::get()->cover_image;
        $url = $this->get_open_graph_image_url($image, '#F3F4F6');

        if ($url) {
            $response['social_media_image'] = $url;
        }

        return $response;
    }

    public function get_open_graph_image_url($square_image_url, $background_color)
    {
        $preset_id = $this->get_or_create_preset_id('podcast_simple');

        if (!$preset_id) {
            return null;
        }

        $base = Plus::base_url().'/media/image/';

        $payload = [
            'modifications' => [
                [
                    'name' => 'url',
                    'src' => $square_image_url
                ],
                [
                    'name' => 'background',
                    'color' => $background_color
                ]
            ]
        ];

        $payload_encoded = base64_encode(wp_json_encode($payload));
        $payload_encoded = rtrim($payload_encoded, '='); // trim padding

        $signature = hash_hmac('sha256', $preset_id.$payload_encoded, $this->token);

        return $base.$preset_id.'/'.$payload_encoded.'/'.$signature.'/image.jpg';
    }

    public function get_or_create_preset_id($template_name)
    {
        $presets = get_option('podlove_plus_image_presets');
    
        if (!$presets) {
            $presets = [];
        }
    
        if (!isset($presets[$template_name])) {
            $response = $this->api->create_image_preset($template_name);
    
            if (is_wp_error($response)) {
                error_log("API Error: " . $response->get_error_message());
                return null;
            }
    
            if (!isset($response['body'])) {
                error_log("API Error: Missing response body for template '$template_name'.");
                return null;
            }
    
            $preset = json_decode($response['body']);
    
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON Decode Error: " . json_last_error_msg() . " in response body: " . $response['body']);
                return null;
            }
    
            if (!isset($preset->id)) {
                error_log("API Response Error: Preset ID missing in response for template '$template_name'. Response body: " . $response['body']);
                return null;
            }
    
            $presets[$template_name] = $preset->id;
            update_option('podlove_plus_image_presets', $presets);
        }
    
        return $presets[$template_name];
    }
}
