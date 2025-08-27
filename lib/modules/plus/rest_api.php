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

        register_rest_route($this->namespace, '/'.$this->rest_base.'/check_file_exists', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'check_file_exists'],
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

        register_rest_route($this->namespace, '/'.$this->rest_base.'/complete_file_upload', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'complete_upload'],
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

        register_rest_route($this->namespace, '/'.$this->rest_base.'/migrate_file', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'migrate_file'],
                'permission_callback' => [$this, 'get_migration_permissions_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/set_migration_complete', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'set_migration_complete'],
                'permission_callback' => [$this, 'get_migration_permissions_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/get_migration_status', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_migration_status'],
                'permission_callback' => [$this, 'get_migration_permissions_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/generate_filename', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'generate_filename'],
                'permission_callback' => [$this, 'get_permissions_check'],
                'args' => [
                    'original_filename' => [
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The original filename to generate new filename from'
                    ],
                    'episode_id' => [
                        'type' => 'integer',
                        'required' => true,
                        'description' => 'The episode ID to generate filename for'
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

    public function check_file_exists($request)
    {
        $filename = $request->get_param('filename');

        return $this->api->check_file_exists($filename);
    }

    public function complete_upload($request)
    {
        $filename = $request->get_param('filename');

        return $this->api->complete_file_upload($filename);
    }

    public function get_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function get_migration_permissions_check($request)
    {
        if (!current_user_can('administrator')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function migrate_file($request)
    {
        $filename = $request->get_param('filename');
        $file_url = $request->get_param('file_url');

        return $this->api->migrate_file($filename, $file_url);
    }

    public function set_migration_complete($request)
    {
        return $this->api->set_migration_complete();
    }

    public function get_migration_status($request)
    {
        return [
            'is_complete' => $this->api->is_migration_complete()
        ];
    }

    public function generate_filename($request)
    {
        $original_filename = $request->get_param('original_filename');
        $episode_id = $request->get_param('episode_id');

        $episode = \Podlove\Model\Episode::find_by_id($episode_id);
        if (!$episode) {
            return new \WP_Error('episode_not_found', 'Episode not found', ['status' => 404]);
        }

        // Use the Auphonic PlusFileTransfer logic for generating filenames
        $filename = \Podlove\Modules\Auphonic\PlusFileTransfer::generate_filename($original_filename, $episode);

        return [
            'original_filename' => $original_filename,
            'generated_filename' => $filename,
            'episode_id' => $episode_id
        ];
    }
}
