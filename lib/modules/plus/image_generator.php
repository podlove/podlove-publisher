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

    public function override_open_graph_image_data($_data)
    {
        $image = \Podlove\Model\Podcast::get()->cover_image;
        $url = $this->get_open_graph_image_url($image, '#F3F4F6');

        return [
            'property' => 'og:image',
            'content' => $url,
        ];
    }

    public function append_to_podcast_api($response)
    {
        $image = \Podlove\Model\Podcast::get()->cover_image;
        $url = $this->get_open_graph_image_url($image, '#F3F4F6');

        $response['social_media_image'] = $url;

        return $response;
    }

    public function get_open_graph_image_url($square_image_url, $background_color)
    {
        $preset_id = $this->get_or_create_preset_id('podcast_simple');

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

        $payload_encoded = base64_encode(json_encode($payload));
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
            $preset = json_decode($response['body']);
            $presets[$template_name] = $preset->id;
            update_option('podlove_plus_image_presets', $presets);
        }

        return $presets[$template_name];
    }
}
