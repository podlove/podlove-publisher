<?php

namespace Podlove\Api\Episodes;

use Podlove\Model\Episode;
use Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;

class WP_REST_PodloveEpisodeRelated_Controller extends \WP_REST_Controller
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
                'args' => [
                    'status' => [
                        'description' => __('Get also episodes with status draft.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'enum' => ['publish', 'draft', 'all']
                    ],
                ],
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_items'],
                'permission_callback' => [$this, 'update_items_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_items'],
                'permission_callback' => [$this, 'delete_items_permissions_check'],
            ]
        ]);
        register_rest_route($this->namespace, '/'.$this->rest_base.'/related', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode relation.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'args' => [
                    'episode_id' => [
                        'description' => __('Identifier for an episode.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'required' => 'true'
                    ],
                    'related_episode_id' => [
                        'description' => __('Identifier for an related Episode.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'required' => 'true'
                    ],
                ],
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ]
        ]);
        register_rest_route($this->namespace, '/'.$this->rest_base.'/related/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode relation.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'args' => [
                    'status' => [
                        'description' => __('Get also episodes with status draft.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'enum' => ['publish', 'draft']
                    ],
                ],
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_items_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'delete_items_permissions_check'],
            ]
        ]);
    }

    public function get_items($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $filter = $request->get_param('status');
        if (!$filter || ($filter != 'draft' && $filter != 'all')) {
            $filter = 'publish';
        }

        $episode = Episode::find_by_id($id);

        if (!$episode || ($filter == 'publish' && !$episode->is_published())) {
            return new \Podlove\Api\Error\NotFoundEpisode($id);
        }

        $relations = EpisodeRelation::find_all_by_where('left_episode_id = '.$episode->id.' OR right_episode_id = '.$episode->id);

        $results = array_map(function ($relation) use ($filter, $episode) {
            $related_id = $relation->left_episode_id;
            $get_left_side = true;
            if ($relation->right_episode_id != $episode->id) {
                $related_id = $relation->right_episode_id;
                $get_left_side = false;
            }
            $related_episode = Episode::find_by_id($related_id);
            if ($related_episode) {
                $related_episode_title = $related_episode->title();
                $post = $related_episode->post();
                if (($filter == 'publish' && $related_episode->is_published())
                     || ($post && $filter == 'draft' && $post->post_status == 'draft')
                    || $filter == 'all') {
                    if ($get_left_side) {
                        return [
                            'episode_releation_id' => $relation->id,
                            'related_episode_id' => $relation->left_episode_id,
                            'related_episode_title' => $related_episode_title
                        ];
                    }
                }

                return [
                    'episode_releation_id' => $relation->id,
                    'related_episode_id' => $relation->right_episode_id,
                    'related_episode_title' => $related_episode_title
                ];
            }
        }, $relations);
        // Delete the invalid entries
        $results = array_filter($results);

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'relatedEpisodes' => $results
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

        $relation = EpisodeRelation::find_by_id($id);

        if (!$relation) {
            $msg = 'sorry, we do not found the episode relation with ID '.$id;

            return new \Podlove\Api\Error\NotFound('rest_not_found', $msg);
        }

        $right_episode = Episode::find_by_id($relation->right_episode_id);
        $left_episode = Episode::find_by_id($relation->left_episode_id);

        if (!$right_episode) {
            return new \Podlove\Api\Error\NotFoundEpisode($relation->right_episode_id);
        }
        if (!$left_episode) {
            return new \Podlove\Api\Error\NotFoundEpisode($relation->left_episode_id);
        }

        if ($isFilter || ($right_episode->is_published() && $left_episode->is_published())) {
            return new \Podlove\Api\Response\OkResponse([
                '_version' => 'v2',
                'episode_id' => $left_episode->id,
                'related_episode_id' => $right_episode->id,
                'related_episode_title' => $right_episode->title()
            ]);
        }

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function get_items_permissions_check($request)
    {
        $filter = $request->get_param('status');
        if ($filter && ($filter == 'draft' || $filter == 'all') && (!current_user_can('edit_posts'))) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function update_items($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFoundEpisode($id);
        }

        // Delete all old items
        $relations = EpisodeRelation::find_all_by_where('left_episode_id = '.$episode->id);
        foreach ($relations as $relation) {
            $relation->delete();
        }

        if (isset($request['related'])) {
            if (is_array($request['related'])) {
                foreach ($request['related'] as $related_id) {
                    $error = $this->create_episode_relation($id, $related_id);
                    if (is_wp_error($error)) {
                        return $error;
                    }
                }
            } else {
                $related_id = $request['related'];
                $error = $this->create_episode_relation($id, $related_id);
                if (is_wp_error($error)) {
                    return $error;
                }
            }
        }

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function update_item($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $relation = EpisodeRelation::find_by_id($id);

        if (!$relation) {
            $msg = 'sorry, we do not found the episode relation with ID '.$id;

            return new \Podlove\Api\Error\NotFound('rest_not_found', $msg);
        }

        if (isset($request['episode_id'])) {
            $episode_id = $request['episode_id'];
            $relation->left_episode_id = $episode_id;
        }

        if (isset($request['related_episode_id'])) {
            $related_id = $request['related_episode_id'];
            $relation->right_episode_id = $related_id;
        }

        $relation->save();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function update_items_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function create_item($request)
    {
        if (isset($request['episode_id'])) {
            $episode_id = $request['episode_id'];
            $episode = Episode::find_by_id($episode_id);
        }

        if (isset($request['related_episode_id'])) {
            $related_id = $request['related_episode_id'];
            $related_episode = Episode::find_by_id($related_id);
        }

        if (!$episode) {
            return new \Podlove\Api\Error\NotFoundEpisode($episode->id);
        }
        if (!$related_episode) {
            return new \Podlove\Api\Error\NotFoundEpisode($related_episode->id);
        }

        $error = $this->create_episode_relation($episode->id, $related_episode->id);
        if (is_wp_error($error)) {
            return $error;
        }

        return new \Podlove\Api\Response\CreateResponse([
            'status' => 'ok',
            'relation_id' => $error
        ]);
    }

    public function create_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function delete_items($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFoundEpisode($id);
        }

        $relations = EpisodeRelation::find_all_by_where('left_episode_id = '.$episode->id);

        foreach ($relations as $relation) {
            $relation->delete();
        }

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function delete_item($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $relation = EpisodeRelation::find_by_id($id);

        if (!$relation) {
            $msg = 'sorry, we do not found the episode relation with ID '.$id;

            return new \Podlove\Api\Error\NotFound('rest_not_found', $msg);
        }

        $relation->delete();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function delete_items_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    private function create_episode_relation($id, $related_id)
    {
        // Don't create duplicates
        $relations = EpisodeRelation::find_all_by_where('left_episode_id = '.intval($id).' AND right_episode_id = '.intval($related_id));
        if ($relations) {
            return;
        }

        $relations = EpisodeRelation::find_all_by_where('right_episode_id = '.intval($id).' AND left_episode_id = '.intval($related_id));
        if ($relations) {
            return;
        }

        $related_episode = Episode::find_by_id($related_id);
        if (!$related_episode) {
            return new \Podlove\Api\Error\NotFoundEpisode($related_id);
        }
        $relation = new EpisodeRelation();
        $relation->left_episode_id = $id;
        $relation->right_episode_id = $related_id;
        $relation->save();

        return $relation->id;
    }
}
