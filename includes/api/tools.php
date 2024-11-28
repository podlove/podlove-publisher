<?php

namespace Podlove\Api\Tools;

add_action('rest_api_init', function () {
    $controller = new WP_REST_Podlove_Tools_Controller();
    $controller->register_routes();
});

class WP_REST_Podlove_Tools_Controller extends \WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'tools';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base.'/clear-caches', [
            'methods' => \WP_REST_Server::DELETABLE,
            'callback' => [$this, 'clear_caches'],
            'permission_callback' => [$this, 'clear_caches_permission_check']
        ]);
    }

    public function clear_caches($request)
    {
        \Podlove\Repair::clear_podlove_cache();
        \Podlove\Repair::clear_podlove_image_cache();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function clear_caches_permission_check()
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }
}
