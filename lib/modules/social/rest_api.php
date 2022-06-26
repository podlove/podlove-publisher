<?php

namespace Podlove\Modules\Social;

use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Social\Model\ContributorService;
use Podlove\Modules\Social\Model\Service;
use Podlove\Modules\Social\Model\ShowService;
use WP_REST_Controller;
use WP_REST_Server;

class REST_API
{
    const api_namespace = 'podlove/v1';
    const api_base = 'social';

    // todo: delete
    // todo: create
    // todo: update

    public function register_routes()
    {
        register_rest_route(self::api_namespace, self::api_base.'/services', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_services'],
                'args' => [
                    'category' => [
                        'description' => __('category: social, donation, internal'),
                        'type' => 'string',
                    ],
                ],
                'permission_callback' => '__return_true',
            ],
        ]);

        register_rest_route(self::api_namespace, self::api_base.'/services/contributor/(?P<id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_contributor_services'],
                'args' => [
                    'id' => [
                        'description' => __('contributor id'),
                        'type' => 'string',
                    ],
                    'category' => [
                        'description' => __('category: social, donation, internal'),
                        'type' => 'string',
                    ]
                ],
                'permission_callback' => '__return_true',
            ],
        ]);
    }

    public function get_services($request)
    {
        $category = $request->get_param('category');
        $services = Service::all();
        $result = [];

        foreach ($services as $service) {
            if (isset($category) == false || $category == $service->category) {
                $item = $service->to_array();
                $item['logo_url'] = $service->image()->url();

                array_push($result, $item);
            }
        }

        return new \WP_REST_Response($result);
    }

    public function get_contributor_services($request)
    {
        $contributor = $request->get_param('id');
        $category = $request->get_param('category');
        $services = ContributorService::find_by_contributor_id_and_category($contributor, $category);

        $entries = array_map(function ($entry) {
            return $entry->to_array();
        }, $services);

        return new \WP_REST_Response($entries);
    }
}


class WP_REST_PodloveContributorService_Controller extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = "podlove/v2";
        $this->rest_base = "social";
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, $this->rest_base.'/services', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'args' => [
                    'category' => [
                        'description' => __('category: social, donation, internal', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                ],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, $this->rest_base.'/services/(?P<id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, $this->rest_base.'/contributors/(?P<id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_contributor_services'],
                'args' => [
                    'id' => [
                        'description' => __('contributor id', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'category' => [
                        'description' => __('category: social, donation, internal', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_contributor_service'],
                'args' => [
                    'id' => [
                        'description' => __('contributor id', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'category' => [
                        'description' => __('category: social, donation, internal', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, $this->rest_base.'/contributors/service/(?P<id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_contributor_service'],
                'args' => [
                    'id' => [
                        'description' => __('contributor social service id', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'category' => [
                        'description' => __('category: social, donation, internal', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_contributor_service'],
                'args' => [
                    'id' => [
                        'description' => __('contributor social service id', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'category' => [
                        'description' => __('category: social, donation, internal', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_contributor_service'],
                'args' => [
                    'id' => [
                        'description' => __('contributor social service id', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'category' => [
                        'description' => __('category: social, donation, internal', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, $this->rest_base.'/podcast', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_podcast_services'],
                'args' => [
                    'category' => [
                        'description' => __('category: social, donation, internal', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_podcast_service'],
                'args' => [
                    'category' => [
                        'description' => __('category: social, donation, internal', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, $this->rest_base.'/podcast/service/(?P<id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_podcast_service'],
                'args' => [
                    'id' => [
                        'description' => __('podcast social service id', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'category' => [
                        'description' => __('category: social, donation, internal', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_podcast_service'],
                'args' => [
                    'id' => [
                        'description' => __('podcast social service id', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'category' => [
                        'description' => __('category: social, donation, internal', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_podcast_service'],
                'args' => [
                    'id' => [
                        'description' => __('podcast social service id', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'category' => [
                        'description' => __('category: social, donation, internal', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ],
        ]);
    }

    public function get_item_permissions_check($request)
    {
        return true;
    }

    public function get_items($request)
    {
        $category = $request->get_param('category');
        $services = Service::all();
        $result = [];

        foreach ($services as $service) {
            if (isset($category) == false || $category == $service->category) {
                $item = $service->to_array();
                $item['logo_url'] = $service->image()->url();

                array_push($result, $item);
            }
        }

        return new \Podlove\Api\Response\OkResponse($result);
    }

    public function get_contributor_services($request)
    {
        $id = $request->get_param('id');
        $contributor = Contributor::find_by_id($id);
        if (!$contributor)
            return new \Podlove\Api\Error\NotFound();

        $category = $request->get_param('category');
        $services = ContributorService::find_by_contributor_id_and_category($id, $category);

        $result = [];
        foreach ($services as $service) {
            $item = $service->to_array();
            $val['id'] = $service->id;
            $val['contributor_id'] = $item['contributor_id'];
            $val['service_id'] = $item['service_id'];
            $val['account_url'] = $service->get_service_url();
            $val['title'] = $item['title'];
            $val['position'] = $item['position'];
            array_push($result, $val);
        }

        return new \Podlove\Api\Response\OkResponse($result);
    }

    public function get_podcast_services($request)
    {
        $category = $request->get_param('category');
        $services = ShowService::find_by_category($category);

        $result = [];
        foreach ($services as $service) {
            $item = $service->to_array();
            $val['id'] = $service->id;
            $val['service_id'] = $item['service_id'];
            $val['account_url'] = $service->get_service_url();
            $val['title'] = $item['title'];
            $val['position'] = $item['position'];
            array_push($result, $val);
        }

        return new \Podlove\Api\Response\OkResponse($result);
    }

    public function get_item($request)
    {
        $id = $request->get_param('id');
        $service = Service::find_by_id($id);
        
        if (!$service) {
            return new \Podlove\Api\Error\NotFound();
        }

        $result = [
            'category' => $service->category,
            'title' => $service->title,
            'description' => $service->description,
            'logo' => $service->logo,
            'url_scheme' => $service->url_scheme,
            'logo_url' => $service->image()->url()
        ];

        return new \Podlove\Api\Response\OkResponse($result);
    }

    public function get_contributor_service($request)
    {
        $id = $request->get_param('id');
        $service = ContributorService::find_by_id($id);
        
        if (!$service) {
            return new \Podlove\Api\Error\NotFound();
        }

        $data = [
            'contributor_id' => $service->contributor_id,
            'service_id' => $service->service_id,
            'account_url' => $service->get_service_url(),
            'title' => $service->title,
            'position' => $service->position
        ];

        return new \Podlove\Api\Response\OkResponse($data);
    }

    public function get_podcast_service($request)
    {
        $id = $request->get_param('id');
        $service = ShowService::find_by_id($id);
        
        if (!$service) {
            return new \Podlove\Api\Error\NotFound();
        }

        $data = [
            'service_id' => $service->service_id,
            'account_url' => $service->get_service_url(),
            'title' => $service->title,
            'position' => $service->position
        ];

        return new \Podlove\Api\Response\OkResponse($data);
    }

    public function create_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function create_contributor_service($request)
    {
        $id = $request->get_param('id');
        $contributor = Contributor::find_by_id($id);
        if (!$contributor)
            return new \Podlove\Api\Error\NotFound();

        $service = new ContributorService();
        $service->contributor_id = $id;
        $service->save();
    
        return new \Podlove\Api\Response\CreateResponse([
            'status' => 'ok',
            'id' => $service->id
        ]);
    }

    public function create_podcast_service($request)
    {
        $service = new ShowService();
        $service->save();

        return new \Podlove\Api\Response\CreateResponse([
            'status' => 'ok',
            'id' => $service->id
        ]);
    }

    public function update_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function update_contributor_service($request)
    {
        $id = $request->get_param('id');
        $service = ContributorService::find_by_id($id);
        
        if (!$service) {
            return new \Podlove\Api\Error\NotFound();
        }

        if (isset($request['contributor_id'])) {
            $cid = $request['contributor_id'];
            $service->contributor_id = $cid;
        }

        if (isset($request['service_id'])) {
            $sid = $request['service_id'];
            $service->service_id = $sid;
        }

        if (isset($request['account'])) {
            $val = $request['account'];
            $service->value = $val;
        }

        if (isset($request['title'])) {
            $title = $request['title'];
            $service->title = $title;
        }

        if (isset($request['position'])) {
            $pos = $request['position'];
            $service->position = $pos;
        }

        $service->save();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function update_podcast_service($request)
    {
        $id = $request->get_param('id');
        $service = ShowService::find_by_id($id);
        
        if (!$service) {
            return new \Podlove\Api\Error\NotFound();
        }

        if (isset($request['service_id'])) {
            $sid = $request['service_id'];
            $service->service_id = $sid;
        }

        if (isset($request['account'])) {
            $val = $request['account'];
            $service->value = $val;
        }

        if (isset($request['title'])) {
            $title = $request['title'];
            $service->title = $title;
        }

        if (isset($request['position'])) {
            $pos = $request['position'];
            $service->position = $pos;
        }

        $service->save();

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

    public function delete_contributor_service($request)
    {
        $id = $request->get_param('id');
        $service = ContributorService::find_by_id($id);
        
        if (!$service) {
            return new \Podlove\Api\Error\NotFound();
        }

        $service->delete();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function delete_podcast_service($request)
    {
        $id = $request->get_param('id');
        $service = ShowService::find_by_id($id);
        
        if (!$service) {
            return new \Podlove\Api\Error\NotFound();
        }

        $service->delete();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

}
