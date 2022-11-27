<?php

namespace Podlove\Api\Episodes;

use Podlove\Model\Episode;
use Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;
use WP_REST_Controller;
use WP_REST_Server;

class WP_REST_PodloveEpisodeRelated_Controller extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'episodes';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)/related', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ]
        ]);
    }

    public function get_item($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $isFilter = true;
        $filter = $request->get_param('status');
        if (!$filter || $filter != 'draft') {
            $isFilter = false;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        $relations = EpisodeRelation::find_all_by_where('left_episode_id = '.$episode->id );

        $results = array_map(function($relation) use ($isFilter) {
            $related_id = $relation->right_episode_id;
            $related_episode = Episode::find_by_id($related_id);
            if ($isFilter || $related_episode->is_published())
                return $relation->right_episode_id;
        }, $relations);
        // Delete the invalid entries
        $results = array_filter($results);

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'releatedEpisodes' => $results
        ]);
    }

    public function get_item_permissions_check($request)
    {
        $filter = $request->get_param('status');
        if ($filter) {
            if ($filter == 'draft') {
                if (!current_user_can('edit_posts')) {
                    return new \Podlove\Api\Error\ForbiddenAccess();
                }
            }
        }
        return true;
    }

    public function update_item($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        if (isset($request['related'])) {
            if (is_array($request['related'])) {
                foreach($request['related'] as $related_id) {
                    $this->create_episode_relation($id, $related_id);
                }
            }
            else {
                $related_id = $request['related'];
                $this->create_episode_relation($id, $related_id);
            }
        }

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }
    
    public function update_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function delete_item($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        $relations = EpisodeRelation::find_all_by_where('left_episode_id = '.$episode->id );

        foreach($relations as $relation) {
            $relation->delete();
        }

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function delete_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    private function create_episode_relation($id, $related_id) 
    {
        $related_episode = Episode::find_by_id($related_id);
        if (!$related_episode) {
            return new \Podlove\Api\Error\NotFound();
        }
        $relation = new EpisodeRelation();
        $relation->left_episode_id = $id;
        $relation->right_episode_id = $related_id;
        $relation->save();
    }
}