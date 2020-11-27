<?php

namespace Podlove\Modules\Social;

use Podlove\Modules\Social\Model\Service;
use Podlove\Modules\Social\Model\ContributorService;

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
