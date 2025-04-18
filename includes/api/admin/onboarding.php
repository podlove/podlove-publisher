<?php

namespace Podlove\Api\Admin;

use Podlove\Modules\Onboarding\Onboarding;

add_action('rest_api_init', function () {
    $controller = new WP_REST_PodloveOnboarding_Controller();
    $controller->register_routes();
});

class WP_REST_PodloveOnboarding_Controller extends \WP_REST_Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'admin';
    }

    /**
     * Register the component routes.
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base.'/onboarding', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_onboarding_options'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_onboarding_options'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
                [
                    'args' => [
                        'banner_hide' => [
                            'description' => __('Hide the banner', 'podlove-podcasting-plugin-for-wordpress'),
                            'type' => 'boolean'
                        ],
                        'type' => [
                            'description' => __('Type of the onboarding', 'podlove-podcasting-plugin-for-wordpress'),
                            'type' => 'string',
                            'enum' => ['start', 'import', 'reset']
                        ],
                    ]
                ]
            ],
        ]);
    }

    /**
     * GET route.
     *
     * @param mixed $request
     */
    public function get_item_permissions_check($request)
    {
        if (!current_user_can('administrator')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function get_onboarding_options($request)
    {
        $banner_flag = Onboarding::is_banner_hide();
        $type = Onboarding::get_onboarding_type();

        return new \Podlove\Api\Response\OkResponse([
            'banner_hide' => $banner_flag,
            'type' => $type
        ]);
    }

    /**
     * PUT/PATCH/POST route.
     *
     * @param mixed $request
     */
    public function update_item_permissions_check($request)
    {
        if (!current_user_can('administrator')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function update_onboarding_options($request)
    {
        if (isset($request['banner_hide'])) {
            $option = $request['banner_hide'];
            Onboarding::set_banner_hide($option);
        }

        if (isset($request['type'])) {
            $option = $request['type'];
            Onboarding::set_onboarding_type($option);
        }

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok',
        ]);
    }
}
