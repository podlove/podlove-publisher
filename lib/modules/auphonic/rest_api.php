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

        register_rest_route(self::api_namespace, self::api_base.'/init-plus-file-transfer/(?P<production_uuid>[A-Za-z0-9\-]+)/(?P<post_id>[0-9]+)', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'init_plus_file_transfer'],
                'permission_callback' => [$this, 'permission_check'],
                'args' => [
                    'production_uuid' => [
                        'required' => true,
                        'type' => 'string',
                        'pattern' => '^[A-Z)a-z0-9\-]+$',
                        'description' => 'The UUID of the Auphonic production'
                    ],
                    'post_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'description' => 'The ID of the post/episode'
                    ]
                ]
            ]
        ]);

        register_rest_route(self::api_namespace, self::api_base.'/transfer-single-file/(?P<production_uuid>[A-Za-z0-9\-]+)/(?P<post_id>[0-9]+)', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'transfer_single_file'],
                'permission_callback' => [$this, 'permission_check'],
                'args' => [
                    'production_uuid' => [
                        'required' => true,
                        'type' => 'string',
                        'pattern' => '^[A-Za-z0-9\-]+$',
                        'description' => 'The UUID of the Auphonic production'
                    ],
                    'post_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'description' => 'The ID of the post/episode'
                    ],
                    'file_data' => [
                        'required' => true,
                        'type' => 'object',
                        'description' => 'File data for transfer'
                    ]
                ]
            ]
        ]);

        register_rest_route(self::api_namespace, self::api_base.'/set-plus-transfer-status/(?P<production_uuid>[A-Za-z0-9\-]+)/(?P<post_id>[0-9]+)', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'set_plus_transfer_status'],
                'permission_callback' => [$this, 'permission_check'],
                'args' => [
                    'production_uuid' => [
                        'required' => true,
                        'type' => 'string',
                        'pattern' => '^[A-Za-z0-9\-]+$',
                        'description' => 'The UUID of the Auphonic production'
                    ],
                    'post_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'description' => 'The ID of the post/episode'
                    ],
                    'status' => [
                        'required' => true,
                        'type' => 'string',
                        'description' => 'Final transfer status'
                    ],
                    'files' => [
                        'required' => false,
                        'type' => 'array',
                        'description' => 'Transfer results'
                    ],
                    'errors' => [
                        'required' => false,
                        'type' => ['string', 'null'],
                        'description' => 'Error message if any'
                    ]
                ]
            ]
        ]);
    }

    public function get_token()
    {
        $key = $this->module->get_module_option('auphonic_api_key');

        return rest_ensure_response($key);
    }

    public function init_plus_file_transfer($request)
    {
        $production_uuid = $request->get_param('production_uuid');
        $post_id = $request->get_param('post_id');

        if (!$production_uuid) {
            return new \WP_Error('invalid_production_uuid', 'Production UUID is required', ['status' => 400]);
        }

        if (!$post_id) {
            return new \WP_Error('invalid_post_id', 'Post ID is required', ['status' => 400]);
        }

        // Verify that the post and production are related
        if (!$this->verify_post_production_relationship($post_id, $production_uuid)) {
            return new \WP_Error('post_production_mismatch', 'The specified post and production are not related', ['status' => 400]);
        }

        // Fetch the production data to get the associated post
        $production_data = $this->module->fetch_production($production_uuid);

        if (!$production_data) {
            return new \WP_Error('production_not_found', 'Could not fetch production data', ['status' => 404]);
        }

        $production = json_decode($production_data, true);

        if (!$production || !isset($production['data'])) {
            return new \WP_Error('invalid_production_data', 'Invalid production data format', ['status' => 400]);
        }

        // Check if production is done
        if ($production['data']['status_string'] !== 'Done') {
            return new \WP_Error('production_not_done', 'Production is not completed yet', ['status' => 400]);
        }

        // Set up $_POST to simulate webhook call
        $_POST['uuid'] = $production_uuid;
        $_POST['status_string'] = 'Done';

        try {
            $this->module->update_production_data($post_id);

            if (\Podlove\Modules\Plus\FileStorage::is_enabled()) {
                $transfer_queue = $this->module->get_plus_transfer_queue($post_id);

                return rest_ensure_response([
                    'success' => true,
                    'message' => 'Transfer queue prepared',
                    'transfer_queue' => $transfer_queue,
                    'post_id' => $post_id,
                    'production_uuid' => $production_uuid
                ]);
            }

            return rest_ensure_response([
                'success' => true,
                'message' => 'PLUS file storage not enabled',
                'transfer_queue' => [],
                'post_id' => $post_id,
                'production_uuid' => $production_uuid
            ]);
        } catch (\Exception $e) {
            return new \WP_Error('transfer_failed', 'Failed to prepare transfer queue: '.$e->getMessage(), ['status' => 500]);
        }
    }

    public function transfer_single_file($request)
    {
        $production_uuid = $request->get_param('production_uuid');
        $post_id = $request->get_param('post_id');
        $file_data = $request->get_param('file_data');

        if (!$production_uuid) {
            return new \WP_Error('invalid_production_uuid', 'Production UUID is required', ['status' => 400]);
        }

        if (!$post_id) {
            return new \WP_Error('invalid_post_id', 'Post ID is required', ['status' => 400]);
        }

        if (!$file_data) {
            return new \WP_Error('invalid_file_data', 'File data is required', ['status' => 400]);
        }

        if (!$this->verify_post_production_relationship($post_id, $production_uuid)) {
            return new \WP_Error('post_production_mismatch', 'The specified post and production are not related', ['status' => 400]);
        }

        try {
            $result = $this->module->transfer_single_plus_file($post_id, $file_data);

            return rest_ensure_response($result);
        } catch (\Exception $e) {
            return new \WP_Error('transfer_failed', 'File transfer failed: '.$e->getMessage(), ['status' => 500]);
        }
    }

    public function set_plus_transfer_status($request)
    {
        $production_uuid = $request->get_param('production_uuid');
        $post_id = $request->get_param('post_id');
        $status = $request->get_param('status');
        $files = $request->get_param('files');
        $errors = $request->get_param('errors');

        if (!$production_uuid) {
            return new \WP_Error('invalid_production_uuid', 'Production UUID is required', ['status' => 400]);
        }

        if (!$post_id) {
            return new \WP_Error('invalid_post_id', 'Post ID is required', ['status' => 400]);
        }

        if (!$status) {
            return new \WP_Error('invalid_status', 'Status is required', ['status' => 400]);
        }

        if (!$this->verify_post_production_relationship($post_id, $production_uuid)) {
            return new \WP_Error('post_production_mismatch', 'The specified post and production are not related', ['status' => 400]);
        }

        try {
            // Convert empty/null errors to null for consistent handling
            $errors = empty($errors) ? null : $errors;

            $this->module->set_plus_transfer_final_status($post_id, $status, $files, $errors);

            return rest_ensure_response([
                'success' => true,
                'message' => 'Transfer status updated successfully'
            ]);
        } catch (\Exception $e) {
            return new \WP_Error('status_update_failed', 'Failed to update transfer status: '.$e->getMessage(), ['status' => 500]);
        }
    }

    public function permission_check()
    {
        if (!current_user_can('edit_posts')) {
            return new \WP_Error('rest_forbidden', 'sorry, you do not have permissions to use this REST API endpoint', ['status' => 401]);
        }

        return true;
    }

    /**
     * Verify that a post and production are related.
     *
     * @param int    $post_id
     * @param string $production_uuid
     *
     * @return bool
     */
    private function verify_post_production_relationship($post_id, $production_uuid)
    {
        return get_post_meta($post_id, 'auphonic_production_id', true) === $production_uuid;
    }
}
