<?php

namespace Podlove\Api\Podcast;

use Podlove\Model\Episode;

add_action('rest_api_init', function () {
    $controller = new WP_REST_PodloveAdmin_Controller();
    $controller->register_routes();
});

/**
 * REST Endpoint for miscellaneous admin tasks that do not fit in the core API.
 *
 * TODO:
 * - restructure so each admin endpoint has its own file
 */
class WP_REST_PodloveAdmin_Controller extends \WP_REST_Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'admin';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base.'/auphonic/webhook_config/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'permissions_check'],
            ],
            [
                'args' => [
                    'authkey' => [
                        'description' => 'Authentication key',
                        'type' => 'string',
                        'required' => 'true'
                    ],
                    'enabled' => [
                        'description' => 'Publish episode when Production is done?',
                        'type' => 'boolean',
                        'required' => 'true'
                    ]
                ],
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'permissions_check'],
            ]
        ]);
    }

    public function get_item($request)
    {
        $id = $request->get_param('id');
        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        return \get_post_meta($episode->post_id, 'auphonic_webhook_config', true);
    }

    public function update_item($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);
        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        $config = \get_post_meta($episode->post_id, 'auphonic_webhook_config', true);

        if (!$config) {
            $config = [];
        }

        if (isset($request['authkey'])) {
            $config['authkey'] = $request['authkey'];
        }

        if (isset($request['enabled'])) {
            $config['enabled'] = $request['enabled'];
        }

        \update_post_meta($episode->post_id, 'auphonic_webhook_config', $config);

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function permissions_check($request)
    {
        return true;
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }
}
