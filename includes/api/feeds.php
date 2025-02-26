<?php

namespace Podlove\Api\Feeds;

use Podlove\Model\Feed;

add_action('rest_api_init', function () {
    $controller = new WP_REST_PodloveFeed_Controller();
    $controller->register_routes();
});

class WP_REST_PodloveFeed_Controller extends \WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'feeds';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
            ]
        ]);
    }

    /**
     * Check if current user has permission to get the list of feeds.
     *
     * @param \WP_REST_Request $request
     *
     * @return bool|\WP_Error
     */
    public function get_items_permissions_check($request)
    {
        // Anyone can read the feeds list
        return true;
    }

    /**
     * Get a list of all feeds.
     *
     * @param \WP_REST_Request $request
     *
     * @return \Podlove\Api\Response\OkResponse
     */
    public function get_items($request)
    {
        $feeds = Feed::find_all_by_property('enable', 1);

        $results = [];

        foreach ($feeds as $feed) {
            // Skip protected and non-discoverable feeds
            if ($feed->protected || !$feed->discoverable) {
                continue;
            }

            $episode_asset = $feed->episode_asset();
            $file_type = $episode_asset ? $episode_asset->file_type() : null;

            $result = [
                'id' => $feed->id,
                'title' => $feed->get_title(),
                'url' => $feed->get_subscribe_url(),
                'content_type' => $feed->get_content_type()
            ];

            if ($file_type) {
                $result['file_type'] = [
                    'name' => $file_type->name,
                    'extension' => $file_type->extension,
                    'mime_type' => $file_type->mime_type
                ];
            }

            $results[] = $result;
        }

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'results' => $results,
        ]);
    }
}
