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

        return new \Podlove\Api\Response\OkResponse();
    }

    public function get_item_permissions_check($request)
    {
        if (!current_user_can('administrator')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }
}
