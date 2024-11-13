<?php

namespace Podlove\Modules\SlackShownotes;

class Slack_Shownotes extends \Podlove\Modules\Base
{
    protected $module_name = 'Slacknotes';
    protected $module_description = 'Extract link lists from a Slack channel to be used in show notes.';
    protected $module_group = 'web publishing';

    public function load()
    {
        add_action('podlove_register_settings_pages', function ($handle) {
            new \Podlove\Modules\SlackShownotes\Settings\Settings($handle);
        });

        add_action('rest_api_init', [$this, 'api_init']);

        add_action('init', [$this, 'register_settings']);
    }

    public function api_init()
    {
        register_rest_route('podlove/v1', 'slacknotes/channels', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_channels'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route('podlove/v1', 'slacknotes/resolve_url', [
            'methods' => 'GET',
            'callback' => [$this, 'api_resolve_url'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route('podlove/v1', 'slacknotes/(?P<channel>[a-zA-Z0-9]+)/messages', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_messages'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route('podlove/v2', 'slacknotes/channels', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_channels'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route('podlove/v2', 'slacknotes/resolve_url', [
            'methods' => 'GET',
            'callback' => [$this, 'api_resolve_url'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route('podlove/v2', 'slacknotes/(?P<channel>[a-zA-Z0-9]+)/messages', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_messages'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    public function api_get_channels(\WP_REST_Request $request)
    {
        if (!$this->get_api_token()) {
            return new \WP_Error(
                'podlove_slacknotes_no_token',
                'Slack api token is missing',
                ['status' => 404]
            );
        }

        $data = $this->get_channels();

        return new \WP_REST_Response($data);
    }

    public function api_resolve_url(\WP_REST_Request $request)
    {
        $url = $request->get_param('url');

        if (!$url) {
            return new \WP_REST_Response(['success' => false]);
        }

        $response = self::fetch_url_meta($url);

        return new \WP_REST_Response($response);
    }

    public function api_get_messages(\WP_REST_Request $request)
    {
        $channel_id = $request->get_param('channel');
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        $data = $this->get_messages($channel_id, $date_from, $date_to);

        return new \WP_REST_Response($data);
    }

    public function register_settings()
    {
        if (!self::is_module_settings_page()) {
            return;
        }

        $this->register_option('slack_api_token', 'password', [
            'label' => __('Slack OAuth Access Token', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => '<a href="https://docs.podlove.org/podlove-publisher/modules/slacknotes/" target="_blank">'.__('Follow guide on how to get the token.', 'podlove-podcasting-plugin-for-wordpress').'</a>',
            'html' => ['class' => 'regular-text'],
        ]);
    }

    public function get_api_token()
    {
        return $this->get_module_option('slack_api_token');
    }

    public function get_channels()
    {
        $curl = new \Podlove\HTTP\Curl();
        $curl->request(
            'https://slack.com/api/conversations.list',
            ['headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->get_api_token(),
            ]]
        );

        $response = $curl->get_response();

        if (!$curl->isSuccessful()) {
            return [];
        }

        $result = json_decode($response['body'], true);

        if (!$result['ok']) {
            return [];
        }

        return array_map(function ($channel) {
            return [
                'id' => $channel['id'],
                'name' => $channel['name'],
            ];
        }, $result['channels']);
    }

    public function get_messages($channel_id, $date_from, $date_to)
    {
        $api_url = 'https://slack.com/api/conversations.history';

        $api_args = ['channel' => $channel_id];

        if ($date_from && $date_to) {
            $api_args['oldest'] = (int) $date_from;
            $api_args['latest'] = (int) $date_to;
        }

        // todo: use has_more field for paging
        $api_args['limit'] = 1000;

        $url = add_query_arg($api_args, $api_url);

        $curl = new \Podlove\HTTP\Curl();
        $curl->request(
            $url,
            ['headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->get_api_token(),
            ]]
        );

        $response = $curl->get_response();

        if (!$curl->isSuccessful()) {
            echo 'curl failed'."\n";

            return [];
        }

        $result = json_decode($response['body'], true);

        if (!$result['ok']) {
            echo 'result not ok'."\n";
            if (isset($result['error'])) {
                echo $result['error']."\n";
            }

            return [];
        }

        return array_map(function ($message) {
            return [
                'raw_slack_message' => $message,
                'links' => Message::extract_links($message),
            ];
        }, $result['messages']);
    }

    /**
     * Fetches title and effective URL for URL.
     *
     * Prefers "og:title", falls back to "title".
     *
     * @param string $url
     *
     * @return string
     */
    public static function fetch_url_meta($url)
    {
        $response = [
            'url' => $url,
            'title' => '',
        ];

        $safe_response = wp_safe_remote_get($url);

        $final_url = array_map(
            function ($entry) { return $entry->url; },
            $safe_response['http_response']->get_response_object()->history
        )[0];

        if ($final_url) {
            $response['url'] = $final_url;
        }

        $html = $safe_response['body'];

        if (!is_wp_error($safe_response)) {
            $dom = new \DOMDocument();
            $loaded = $dom->loadHTML($html, LIBXML_NOERROR);

            if (!$loaded) {
                return $response;
            }

            foreach ($dom->getElementsByTagName('meta') as $node) {
                if ($node->getAttribute('property') == 'og:title') {
                    $response['title'] = $node->getAttribute('content');

                    return $response;
                }
            }

            foreach ($dom->getElementsByTagName('title') as $node) {
                $response['title'] = $node->nodeValue;

                return $response;
            }
        }

        return $response;
    }

    public function permission_check()
    {
        if (!current_user_can('edit_posts')) {
            return new \WP_Error('rest_forbidden', 'sorry, you do not have permissions to use this REST API endpoint', ['status' => 401]);
        }

        return true;
    }
}
