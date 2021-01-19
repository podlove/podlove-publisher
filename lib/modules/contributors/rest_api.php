<?php

namespace Podlove\Modules\Contributors;

use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Contributors\Model\ContributorGroup;
use Podlove\Modules\Contributors\Model\ContributorRole;
use Podlove\Modules\Contributors\Model\EpisodeContribution;

class REST_API
{
    const api_namespace = 'podlove/v1';
    const api_base = 'contributors';

    // todo: delete
    // todo: create
    // todo: update

    public function register_routes()
    {
        register_rest_route(self::api_namespace, self::api_base, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_contributors'],
                'permission_callback' => '__return_true',
            ],
        ]);
        register_rest_route(self::api_namespace, self::api_base.'/groups', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_groups'],
                'permission_callback' => '__return_true',
            ],
        ]);
        register_rest_route(self::api_namespace, self::api_base.'/roles', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_roles'],
                'permission_callback' => '__return_true',
            ],
        ]);
        register_rest_route(self::api_namespace, self::api_base.'/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for contributor.'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_contributor'],
                'permission_callback' => '__return_true',
            ],
        ]);
        register_rest_route(self::api_namespace, self::api_base.'/episode/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for episode.'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_episode'],
                'permission_callback' => '__return_true',
            ],
        ]);
    }

    public function get_contributors()
    {
        $entries = Contributor::all();
        $entries = array_reduce($entries, function ($result = [], $contributor) {
            if ($contributor->visibility != 1) {
                return $result;
            }

            array_push($result, $this->filter_contributor($contributor));

            return $result;
        }, []);

        return new \WP_REST_Response($entries);
    }

    public function get_groups()
    {
        $groups = ContributorGroup::all();

        $entries = array_map(function ($entry) {
            return $entry->to_array();
        }, $groups);

        return new \WP_REST_Response($entries);
    }

    public function get_roles()
    {
        $roles = ContributorRole::all();

        $entries = array_map(function ($entry) {
            return $entry->to_array();
        }, $roles);

        return new \WP_REST_Response($entries);
    }

    public function get_contributor($request)
    {
        $id = $request->get_param('id');
        $contributor = Contributor::find_by_id($id);

        if (!isset($contributor)) {
            return new \WP_Error(
                'podlove_rest_contributor_not_found',
                'contributor not found',
                ['status' => 404]
            );
        }

        return new \WP_REST_Response($this->filter_contributor($contributor));
    }

    public function get_episode($request)
    {
        $id = $request->get_param('id');

        $results = array_map(function ($contributor) {
            return [
                'id' => $contributor->contributor_id,
                'role' => $contributor->role_id,
                'group' => $contributor->group_id,
            ];
        }, EpisodeContribution::find_all_by_episode_id($id));

        return new \WP_REST_Response($results);
    }

    private function filter_contributor($contributor)
    {
        return [
            'id' => $contributor->id,
            'slug' => $contributor->identifier,
            'avatar' => $contributor->avatar,
            'name' => $contributor->getName(),
            'mail' => $contributor->publicemail,
            'department' => $contributor->department,
            'organisation' => $contributor->organisation,
            'jobtitle' => $contributor->jobtitle,
            'gender' => $contributor->gender,
            'nickname' => $contributor->nickname,
            'count' => $contributor->contributioncount,
        ];
    }
}
