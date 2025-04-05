<?php

namespace Podlove\Modules\Plus;

class RestApi extends \WP_REST_Controller
{
    private $api;

    public function __construct(API $api)
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'plus';

        $this->api = $api;
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base.'/create_file_upload', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_upload_url'],
                'permission_callback' => [$this, 'get_permissions_check'],
                [
                    'args' => [
                        'filename' => [
                            'type' => 'string'
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function create_upload_url($request)
    {
        $filename = $request->get_param('filename');

        return $this->api->create_file_upload($filename);
    }

    public function get_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }
}
