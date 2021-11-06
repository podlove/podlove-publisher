<?php

namespace Podlove\Api\Chapters;

use Podlove\Model\Episode;
use WP_REST_Controller;
use WP_REST_Server;

add_action('rest_api_init', function () {
    $controller = new WP_REST_PodloveChapters_Controller();
    $controller->register_routes();
});

class WP_REST_PodloveChapters_Controller extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'chapters';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],

            ]
        ]);

    }

    public function get_item_permissions_check( $request )
    {
        return true;
    }

    public function get_item( $request )
    {
        $id = $request->get_param('id');
        $episode = Episode::find_by_id($id);

        if ($episode) {
            $data = array_map(function ($c) {
                $c->title = html_entity_decode(trim($c->title));
        
                return $c;
            }, (array) json_decode($episode->get_chapters('json')));
        }

        return new \Podlove\Api\Response\OkResponse([
            'chapters' => $data,
            '_version' => 'v2',
        ]);
    
    }

    public function create_item_permissions_check( $request )
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }
        return true;
    }

    public function create_item( $request )
    {        
    }

    public function update_item_permissions_check( $request )
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function update_item( $request )
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok' 
        ]);
    }

    public function delete_item_permissions_check( $request )
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function delete_item( $request )
    {
        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok' 
        ]);
    }
}