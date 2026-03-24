<?php

namespace Podlove\Api\Admin;

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Modules\Plus\FileStorage;

add_action('rest_api_init', function () {
    $controller = new WP_REST_PodlovePlus_Controller();
    $controller->register_routes();
});

class WP_REST_PodlovePlus_Controller extends \WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'admin/plus';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base.'/episodes_for_migration', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_episodes_for_migration'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/features', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_features'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/set_feature', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'set_feature'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/token', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_token'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/validate_token', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'validate_token'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/save_token', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'save_token'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);
    }

    public function get_episodes_for_migration($request)
    {
        $episodes = Episode::find_all_by_time();

        if (empty($episodes)) {
            return new \Podlove\Api\Response\OkResponse([
                'episodes' => [],
            ]);
        }

        $episodes_with_files = [];

        foreach ($episodes as $episode) {
            $media_files = $episode->media_files();
            $episode_title = $episode->title();

            $files = array_map(function ($file) {
                $local_url = FileStorage::get_local_file_url($file);
                $plus_url = $file->get_file_url();

                return [
                    'local_url' => $local_url,
                    'plus_url' => $plus_url,
                    'filename' => $file->get_file_name(),
                ];
            }, $media_files);

            $episodes_with_files[] = [
                'episode_title' => $episode_title,
                'files' => $files,
            ];
        }

        return new \Podlove\Api\Response\OkResponse([
            'episodes' => $episodes_with_files,
        ]);
    }

    public function get_features($request)
    {
        $podcast = Podcast::get();

        return new \Podlove\Api\Response\OkResponse([
            'file_storage' => $podcast->plus_enable_storage,
            'feed_proxy' => $podcast->plus_enable_proxy,
        ]);
    }

    public function set_feature($request)
    {
        $feature = $request->get_param('feature');
        $value = (bool) $request->get_param('value');
        $valid_features = ['fileStorage', 'feedProxy'];

        if (!in_array($feature, $valid_features)) {
            return new \Podlove\Api\Error\ArgumentError(message: 'Invalid feature');
        }

        $podcast = Podcast::get();

        if ($feature === 'fileStorage') {
            $podcast->plus_enable_storage = $value;
        }

        if ($feature === 'feedProxy') {
            $podcast->plus_enable_proxy = $value;
        }

        $podcast->save();

        if ($feature === 'fileStorage') {
            do_action('podlove_plus_enable_storage_changed', $value);
        }

        if ($feature === 'feedProxy') {
            do_action('podlove_plus_enable_proxy_changed', $value);
        }

        return new \Podlove\Api\Response\OkResponse();
    }

    public function get_token($request)
    {
        $plus_module = \Podlove\Modules\Plus\Plus::instance();
        $token = $plus_module->get_module_option('plus_api_token');

        return new \Podlove\Api\Response\OkResponse([
            'token' => $token ?: ''
        ]);
    }

    public function validate_token($request)
    {
        $plus_module = \Podlove\Modules\Plus\Plus::instance();
        $token = $plus_module->get_module_option('plus_api_token');

        if (!$token) {
            return new \Podlove\Api\Response\OkResponse([
                'user' => null
            ]);
        }

        $api = new \Podlove\Modules\Plus\API($plus_module, $token);
        $user = $api->get_me();

        if ($user && isset($user->email)) {
            return new \Podlove\Api\Response\OkResponse([
                'user' => [
                    'email' => $user->email
                ]
            ]);
        }

        return new \Podlove\Api\Response\OkResponse([
            'user' => null
        ]);
    }

    public function save_token($request)
    {
        $token = $request->get_param('token');
        $token = sanitize_text_field($token);

        $plus_module = \Podlove\Modules\Plus\Plus::instance();
        $plus_module->update_module_option('plus_api_token', $token);

        return new \Podlove\Api\Response\OkResponse([
            'success' => true
        ]);
    }

    public function get_item_permissions_check($request)
    {
        if (!current_user_can('administrator')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }
}
