<?php

namespace Podlove\Modules\Contributors;

use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Contributors\Model\ContributorGroup;
use Podlove\Modules\Contributors\Model\ContributorRole;
use Podlove\Modules\Contributors\Model\DefaultContribution;

use Podlove\Modules\Contributors\Model\EpisodeContribution;
use WP_REST_Controller;

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
        $entries = array_reduce($entries, function ($result, $contributor) {
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
                'args' => [
                    'filter' => [
                        'description' => __('The filter parameter is used to filter the collection of contributors.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'enum' => ['all', 'visible']
                    ]
                ],
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
        register_rest_route($this->namespace, $this->rest_base.'/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for contributor.', 'podlove-podcasting-plugin-for-wordpress'),
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
                        'description' => __('Gender of the contributor', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'enum' => ['female', 'male', 'Not attributed']
                    ],
                    'visibility' => [
                        'description' => __('Should the participation of the contributor be publicily visible?', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'enum' => ['yes', 'no']
                    ],
                    'identifier' => [
                        'description' => __('identifier', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'organisation' => [
                        'description' => __('Organisation', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'department' => [
                        'description' => __('Department', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'jobtitle' => [
                        'description' => __('Jobtitle of the contributor', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'realname' => [
                        'description' => __('Name of the contributor', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'nickname' => [
                        'description' => __('Nickname of the contributor', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'publicname' => [
                        'description' => __('Used name in the blog', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'avatar' => [
                        'description' => __('Avatar of the contributor', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::url'
                    ],
                    'email' => [
                        'description' => __('e-mail of the contributor Do not use external.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'format' => 'email',
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
        register_rest_route($this->namespace, $this->rest_base.'/groups', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items_group'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item_group'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
        ]);
        register_rest_route($this->namespace, $this->rest_base.'/groups/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for contributor group.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item_group'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item_group'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
                'args' => [
                    'title' => [
                        'description' => __('Title of the contributor group', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'required' => 'true',
                        'validate_callback' => '\Podlove\Api\Validation::maxLength255'
                    ],
                    'slug' => [
                        'description' => __('Slug of the contributor group', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'required' => 'true',
                        'validate_callback' => '\Podlove\Api\Validation::maxLength255'
                    ],
                ],
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item_group'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ],
        ]);
        register_rest_route($this->namespace, $this->rest_base.'/roles', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items_role'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item_role'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
        ]);
        register_rest_route($this->namespace, $this->rest_base.'/roles/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for contributor role.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item_role'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item_role'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
                'args' => [
                    'title' => [
                        'description' => __('Title of the contributor role', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'required' => 'true',
                        'validate_callback' => '\Podlove\Api\Validation::maxLength255'
                    ],
                    'slug' => [
                        'description' => __('Slug of the contributor role', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'required' => 'true',
                        'validate_callback' => '\Podlove\Api\Validation::maxLength255'
                    ],
                ],
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item_role'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ],
        ]);
        register_rest_route($this->namespace, $this->rest_base.'/defaults', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items_default'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item_default'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
        ]);
        register_rest_route($this->namespace, $this->rest_base.'/defaults/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for defaults contributor.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item_default'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item_default'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
                'args' => [
                    'contributor_id' => [
                        'description' => __('Contributor ID of the default contributor', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                        'required' => 'true',
                        'validate_callback' => '\Podlove\Api\Validation::isContributorIdExist'
                    ],
                    'show_id' => [
                        'description' => __('Show ID of the default contributor', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                        'required' => 'true',
                    ],
                     'group_id' => [
                        'description' => __('Group ID of the default contributor', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                        'validate_callback' => '\Podlove\Api\Validation::isContributorGroupIdExist'
                    ],
                    'role_id' => [
                        'description' => __('Role ID of the contributor group', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                        'validate_callback' => '\Podlove\Api\Validation::isContributorRoleIdExist'
                    ],
                    'position' => [
                        'description' => __('Position of the default contributor in the list', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                    ],
                    'comment' => [
                        'description' => __('Comment to the default contributor', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                  ],
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item_default'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ],
        ]);
        register_rest_route($this->namespace, $this->rest_base.'/(?P<id>[\d]+)/episodes', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for contributor.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_episodes'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);
    }

    public function get_items($request)
    {
        $filter = $request->get_param('filter');
        if (!$filter || $filter != 'all') {
            $filter = 'visible';
        }

        $entries = Contributor::all();

        $result = [];
        for ($i = 0; $i < count($entries); ++$i) {
            if ($filter == 'visible') {
                if ($entries[$i]->visibility == 1) {
                    array_push($result, $this->get_contributor_data($entries[$i]));
                }
            } else {
                if ($filter == 'all') {
                    array_push($result, $this->get_contributor_data_full($entries[$i]));
                }
            }
        }

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'contributors' => $result
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

    public function get_item_group($request)
    {
        $id = $request->get_param('id');
        $group = ContributorGroup::find_by_id($id);

        if (!isset($group)) {
            return new \Podlove\Api\Error\NotFound();
        }

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'id' => $group->id,
            'title' => $group->title,
            'slug' => $group->slug
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

    public function get_item_role($request)
    {
        $id = $request->get_param('id');
        $role = ContributorRole::find_by_id($id);

        if (!isset($role)) {
            return new \Podlove\Api\Error\NotFound();
        }

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'id' => $role->id,
            'title' => $role->title,
            'slug' => $role->slug
        ]);
    }

    public function get_items_default($request)
    {
        $defaults = DefaultContribution::all();

        $entries = array_map(function($entry) {
            return $entry->to_array();
        }, $defaults);

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'defaults' => $entries
        ]);
    }

    public function get_item_default($request)
    {
        $id = $request->get_param('id');
        $default = DefaultContribution::find_by_id($id);

        if (!isset($default)) {
            return new \Podlove\Api\Error\NotFound();
        }

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'id' => $default->id,
            'contributor_id' => $default->contributor_id,
            'show_id' => $default->show_id,
            'group_id' => $default->group_id,
            'role_id' => $default->role_id,
            'position' => $default->position,
            'comment' => $default->comment
        ]);
    }

    public function get_item($request)
    {
        $filter = $request->get_param('filter');
        if (!$filter || $filter != 'all') {
            $filter = 'visible';
        }

        $id = $request->get_param('id');
        $contributor = Contributor::find_by_id($id);

        if (!isset($contributor)) {
            return new \Podlove\Api\Error\NotFound();
        }

        if ($filter == 'visible') {
            if ($contributor->visibility != 1) {
                return new \Podlove\Api\Error\ForbiddenAccess();
            }
        }

        if ($filter == 'all')
            $result = $this->get_contributor_data_full($contributor);
        else
            $result = $this->get_contributor_data($contributor);

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'contributor' => $result
        ]);
    }

    public function get_item_permissions_check($request)
    {
        $filter = $request->get_param('filter');
        if ($filter) {
            if ($filter == 'all') {
                if (!current_user_can('edit_posts')) {
                    return new \Podlove\Api\Error\ForbiddenAccess();
                }

                return true;
            }

            return true;
        }

        return true;
    }

    public function create_item($request)
    {
        $contributor = new Contributor();
        $contributor->visibility = 1;
        $contributor->contributioncount = 0;
        $contributor->save();

        return new \Podlove\Api\Response\CreateResponse([
            'status' => 'ok',
            'id' => $contributor->id
        ]);
    }

    public function create_item_group($request)
    {
        $group = new ContributorGroup();
        $group->save();

        return new \Podlove\Api\Response\CreateResponse([
            'status' => 'ok',
            'id' => $group->id
        ]);
    }

    public function create_item_role($request)
    {
        $role = new ContributorRole();
        $role->save();

        return new \Podlove\Api\Response\CreateResponse([
            'status' => 'ok',
            'id' => $role->id
        ]);
    }

    public function create_item_default($request)
    {
        $default = new DefaultContribution();
        $default->save();

        return new \Podlove\Api\Response\CreateResponse([
            'status' => 'ok',
            'id' => $default->id
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
            if ($gender === 'Not attributed') {
                $contributor->gender = 'none';
            } else {
                $contributor->gender = $gender;
            }
        }

        if (isset($request['identifier'])) {
            $identifier = $request['identifier'];
            $contributor->identifier = $identifier;
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

        if (isset($request['visibility'])) {
            $visibility = $request['visibility'];
            if ($visibility == 'no') {
                $contributor->visibility = 0;
            }
            if ($visibility == 'yes') {
                $contributor->visibility = 1;
            }
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

    public function update_item_group($request)
    {
        $id = $request->get_param('id');
        $group = ContributorGroup::find_by_id($id);

        if (!isset($group)) {
            return new \Podlove\Api\Error\NotFound();
        }

        if (isset($request['title'])) {
            $title = $request['title'];
            $group->title = $title;
        }

        if (isset($request['slug'])) {
            $slug = $request['slug'];
            $group->slug = $slug;
        }

        $group->save();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function update_item_role($request)
    {
        $id = $request->get_param('id');
        $role = ContributorRole::find_by_id($id);

        if (!isset($role)) {
            return new \Podlove\Api\Error\NotFound();
        }

        if (isset($request['title'])) {
            $title = $request['title'];
            $role->title = $title;
        }

        if (isset($request['slug'])) {
            $slug = $request['slug'];
            $role->slug = $slug;
        }

        $role->save();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function update_item_default($request)
    {
        $id = $request->get_param('id');
        $default = DefaultContribution::find_by_id(($id));

        if (!isset($default)) {
            return new \Podlove\Api\Error\NotFound();
        }

        if (isset($request['contributor_id'])) {
            $contributor_id = $request['contributor_id'];
            $default->contributor_id = $contributor_id;
        }

        if (isset($request['group_id'])) {
            $group_id = $request['group_id'];
            $default->group_id = $group_id;
        }

        if (isset($request['role_id'])) {
            $role_id = $request['role_id'];
            $default->role_id = $role_id;
        }

        if (isset($request['position'])) {
            $position = $request['position'];
            $default->position = $position;
        }

        if (isset($request['comment'])) {
            $comment = $request['comment'];
            $default->comment = $comment;
        }

        $default->save();

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

    public function delete_item_group($request)
    {
        $id = $request->get_param('id');
        $group = ContributorGroup::find_by_id($id);

        if (!isset($group)) {
            return new \Podlove\Api\Error\NotFound();
        }

        $group->delete();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function delete_item_role($request)
    {
        $id = $request->get_param('id');
        $role = ContributorRole::find_by_id($id);

        if (!isset($role)) {
            return new \Podlove\Api\Error\NotFound();
        }

        $role->delete();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function delete_item_default($request)
    {
        $id = $request->get_param('id');
        $default = DefaultContribution::find_by_id(($id));

        if (!isset($default)) {
            return new \Podlove\Api\Error\NotFound();
        }

        $default->delete();

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

    public function get_episodes($request)
    {
        $id = $request->get_param('id');

        $results = array_map(function ($contributor) {
            return [
                'epsiode_id' => $contributor->episode_id
            ];
        }, EpisodeContribution::find_all_by_contributor_id($id));

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'episodes' => $results
        ]);
    }

    private function get_contributor_data($contributor)
    {
        return [
            'id' => $contributor->id,
            'identifier' => $contributor->identifier,
            'avatar_url' => $contributor->avatar()->url(),
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

    private function get_contributor_data_full($contributor)
    {
        return [
            'id' => $contributor->id,
            'identifier' => $contributor->identifier,
            'visibility' => $contributor->visibility,
            'avatar' => $contributor->avatar,
            'avatar_url' => $contributor->avatar()->url(),
            'publicname' => $contributor->publicname,
            'nickname' => $contributor->nickname,
            'realname' => $contributor->realname,
            'mail' => $contributor->publicemail,
            'department' => $contributor->department,
            'organisation' => $contributor->organisation,
            'jobtitle' => $contributor->jobtitle,
            'gender' => $contributor->gender,
            'count' => $contributor->contributioncount,
        ];
    }

}
