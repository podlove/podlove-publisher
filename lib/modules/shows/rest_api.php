<?php

namespace Podlove\Modules\Shows;

use Podlove\Model\Episode;
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

        register_rest_route(self::api_namespace, self::api_base.'/next_episode_number', [
            [
                'args' => [
                    'show' => [
                        'description' => 'show slug',
                        'type' => 'string'
                    ]
                ],
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_next_episode_number'],
                'permission_callback' => [$this, 'permission_check'],
            ]
        ]);
    }

    public function get_items($request)
    {
        $shows = Show::all();

        $shows = array_map(function ($show) {
            $show = (array) $show;
            $show['feeds'] = \Podlove\Api\Feeds\WP_REST_PodloveFeed_Controller::get_feeds('shows', $show['id']);

            return $show;
        }, $shows);

        return rest_ensure_response($shows);
    }

    public function get_next_episode_number($request)
    {
        $slug = $request->get_param('show');
        $show = $slug ? Show::find_one_term_by_property('slug', $slug) : null;

        return Episode::get_next_episode_number($show ? $show->slug : null);
    }

    public function permission_check()
    {
        if (!current_user_can('edit_posts')) {
            return new \WP_Error('rest_forbidden', 'sorry, you do not have permissions to use this REST API endpoint', ['status' => 401]);
        }

        return true;
    }
}
