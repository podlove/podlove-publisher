<?php

namespace Podlove\Modules\Shows;

use Podlove\Modules\Shows\Model\Show;

class REST_API
{
    const api_namespace = 'podlove/v2';
    const api_base = 'shows';

    public function register_routes()
    {
        register_rest_route(self::api_namespace, self::api_base, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'permission_check'],
            ]
        ]);
    }

    public function get_items($request)
    {
        $shows = Show::all();

        return rest_ensure_response($shows);
    }

    public function permission_check()
    {
        if (!current_user_can('edit_posts')) {
            return new \WP_Error('rest_forbidden', 'sorry, you do not have permissions to use this REST API endpoint', ['status' => 401]);
        }

        return true;
    }
}
