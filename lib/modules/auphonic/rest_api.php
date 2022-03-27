<?php

namespace Podlove\Modules\Auphonic;

class REST_API
{
    const api_namespace = 'podlove/v2';
    const api_base = 'auphonic';

    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function register_routes()
    {
        register_rest_route(self::api_namespace, self::api_base.'/token', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_token'],
                'permission_callback' => [$this, 'permission_check'],
            ]
        ]);
    }

    public function get_token()
    {
        $key = $this->module->get_module_option('auphonic_api_key');

        return rest_ensure_response($key);
    }

    public function permission_check()
    {
        if (!current_user_can('edit_posts')) {
            return new \WP_Error('rest_forbidden', 'sorry, you do not have permissions to use this REST API endpoint', ['status' => 401]);
        }

        return true;
    }
}
