<?php

namespace Podlove\Modules\Contributors;

use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Contributors\Model\ContributorGroup;
use Podlove\Modules\Contributors\Model\ContributorRole;
use Podlove\Modules\Contributors\Model\EpisodeContribution;

use WP_REST_Controller;
use WP_REST_Server;

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

class WP_REST_PodloveContributors_Controller extends WP_REST_Controller
{

    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'contributors';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, $this->rest_base, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ]
        ]);
        register_rest_route($this->namespace, $this->rest_base.'/groups', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items_group'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);
        register_rest_route($this->namespace, $this->rest_base.'/roles', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items_role'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);
        register_rest_route($this->namespace, $this->rest_base.'/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for contributor.'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'args' => [
                    'gender' => [
                        'description' => __('Gender of the contributor'),
                        'type' => 'string',
                        'enum' => array('female', 'male', 'Not attributed')
                    ],
                    'visibility' => [
                        'description' => __('Should the participation of the contributor be publicily visible?'),
                        'type' => 'string',
                        'enum' => array('yes', 'no')
                    ],
                    'organisation' => [
                        'description' => __('Organisation'),
                        'type' => 'string',
                    ],
                    'department' => [
                        'description' => __('Department'),
                        'type' => 'string',
                    ],
                    'jobtitle' => [
                        'description' => __('Jobtitle of the contributor'),
                        'type' => 'string',
                    ],
                    'realname' => [
                        'description' => __('Name of the contributor'),
                        'type' => 'string',
                    ],
                    'nickname' => [
                        'description' => __('Nickname of the contributor'),
                        'type' => 'string',
                    ],
                    'publicname' => [
                        'description' => __('Used name in the blog'),
                        'type' => 'string',
                    ],
                    'avatar' => [
                        'description' => __('Avatar of the contrbutor'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::url'
                    ],
                    'email' => [
                        'description' => __('e-mail of the contributor Do not use external.'),
                        'type' => 'string',
                    ],
                ],
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ]
        ]);
        register_rest_route($this->namespace, $this->rest_base.'/episode/(?P<id>[\d]+)', [
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

    public function get_items($request)
    {
        $entries = Contributor::all();
        $entries = array_reduce($entries, function ($result = [], $contributor) {
            if ($contributor->visibility != 1) {
                return $result;
            }

            array_push($result, $this->filter_contributor($contributor));

            return $result;
        }, []);

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'contributors' => $entries
        ]);
    }

    public function get_items_group($request)
    {
        $groups = ContributorGroup::all();

        $entries = array_map(function ($entry) {
            return $entry->to_array();
        }, $groups);

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'groups' => $entries
        ]);
    }

    public function get_items_role($request)
    {
        $roles = ContributorRole::all();

        $entries = array_map(function ($entry) {
            return $entry->to_array();
        }, $roles);

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'roles' => $entries
        ]);
    }

    public function get_item($request)
    {
        $id = $request->get_param('id');
        $contributor = Contributor::find_by_id($id);

        if (!isset($contributor)) {
            return new \Podlove\Api\Error\NotFound();
        }

        return new \WP_REST_Response($this->filter_contributor($contributor));
    }

    public function get_item_permissions_check($request)
    {
        return true;
    }

    public function create_item($request)
    {
        $contributor = new Contributor();
        $contributor->visibilty = 0;
        $contributor->contributioncount = 0;
        $contributor->save();

        return new \Podlove\Api\Response\CreateResponse([
            'status' => 'ok',
            'id' => $contributor->id
        ]);
    }

    public function create_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }
        return true;
    }

    public function update_item($request)
    {
        $id = $request->get_param('id');
        $contributor = Contributor::find_by_id($id);

        if (!isset($contributor)) {
            return new \Podlove\Api\Error\NotFound();
        }

        if (isset($request['gender'])) {
            $gender = $request['gender'];
            $contributor->gender = $gender;
        }

        if (isset($request['organisation'])) {
            $organisation = $request['organisation'];
            $contributor->organisation = $organisation;
        }

        if (isset($request['department'])) {
            $department = $request['department'];
            $contributor->department = $department;
        }

        if (isset($request['jobtitle'])) {
            $jobtitle = $request['jobtitle'];
            $contributor->jobtitle = $jobtitle;
        }

        if (isset($request['realname'])) {
            $realname = $request['realname'];
            $contributor->realname = $realname;
        }

        if (isset($request['nickname'])) {
            $nickname = $request['nickname'];
            $contributor->nickname = $nickname;
        }

        if (isset($request['publicname'])) {
            $publicname = $request['publicname'];
            $contributor->publicname = $publicname;
        }

        if (isset($request['avatar'])) {
            $avatar = $request['avatar'];
            $contributor->avatar = $avatar;
        }

        if (isset($request['visibilty'])) {
            $visibilty = $request['visibilty'];
            if ($visibilty === 'no')
                $contributor->visibility = 0;
            if ($visibilty === 'yes')
                $contributor->visibility = 1;
        }

        if (isset($request['email'])) {
            $privateemail = $request['email'];
            $contributor->privateemail = $privateemail;
        }

        $contributor->save();

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
        $contributor = Contributor::find_by_id($id);

        if (!isset($contributor)) {
            return new \Podlove\Api\Error\NotFound();
        }

        $contributor->delete();

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
