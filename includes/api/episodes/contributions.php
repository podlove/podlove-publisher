<?php

namespace Podlove\Api\Episodes;

use Podlove\Model\Episode;
use Podlove\Modules\Contributors\Model\EpisodeContribution;
use WP_REST_Controller;
use WP_REST_Server;

class WP_REST_PodloveEpisodeContributions_Controller extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'episodes';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)/contributions', [
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
                'args' => [
                    'contributors' => [
                        'description' => __('List of contributors of the episode', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'contributor_id' => [
                                    'description' => __('Id of a contributor'),
                                    'type' => 'integer',
                                    'required' => 'true'
                                ],
                                'group_id' => [
                                    'description' => __('Id of group of the contributor', 'podlove-podcasting-plugin-for-wordpress'),
                                    'type' => 'integer',
                                ],
                                'role_id' => [
                                    'description' => __('Id of role of the contributor', 'podlove-podcasting-plugin-for-wordpress'),
                                    'type' => 'integer',
                                ],
                                'comment' => [
                                    'description' => __('Comment to the contributor', 'podlove-podcasting-plugin-for-wordpress'),
                                    'type' => 'string'
                                ]
                            ]
                        ]
                    ]
                ],
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

        $results = array_map(function ($contributor) {
            return [
                'contributor_id' => $contributor->contributor_id,
                'role_id' => $contributor->role_id,
                'group_id' => $contributor->group_id,
                'comment' => $contributor->comment,
            ];
        }, EpisodeContribution::find_all_by_episode_id($id));

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'contributors' => $results
        ]);
    }

    public function get_item_permissions_check($request)
    {
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

        if (isset($request['contributors']) && is_array($request['contributors'])) {
            for ($i = 0; $i < count($request['contributors']); ++$i) {
                $contrib = new EpisodeContribution();
                $contrib->episode_id = $id;
                if (isset($request['contributors'][$i]['contributor_id'])) {
                    $contrib->contributor_id = $request['contributors'][$i]['contributor_id'];
                }
                if (isset($request['contributors'][$i]['group_id'])) {
                    $contrib->group = $request['contributors'][$i]['group_id'];
                }
                if (isset($request['contributors'][$i]['role_id'])) {
                    $contrib->role = $request['contributors'][$i]['role_id'];
                }
                if (isset($request['contributors'][$i]['comment'])) {
                    $contrib->comment = $request['contributors'][$i]['comment'];
                }
                $contrib->save();
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

        $contributors = EpisodeContribution::find_all_by_episode_id($id);

        foreach ($contributors as $contributor) {
            $contributor->delete();
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
}
