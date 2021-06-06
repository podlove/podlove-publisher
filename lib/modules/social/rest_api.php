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

        register_rest_route(self::api_namespace, self::api_base.'/lookup/(?P<service>.+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'lookup_person'],
                'args' => [
                    'service' => [
                        'description' => __('lookup service: webfinger, gravatar.com, etc'),
                        'default' => 'webfinger',
                        'type' => 'enum',
                        'enum' => ['webfinger', 'json', 'gravatar.com']
                    ],
                    'id' => [
                        'description' => __('contributor id'),
                        'type' => 'string',
                        'required' => true

                    ]
                ],
                //'permission_callback' => "__return current_user_can( 'edit_posts' );",
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

    public function lookup_person($request)
    {
        $service = $request->get_param('service');
        $contributor = $request->get_param('id');
        $clean = function ($x) { return $x; };

        switch ( $service ) {
            case 'gravatar.com':
                // $contributor needs to be either hash of email address or gravatar.com username
                // if email, than create md5 hash
                if (strpos($contributor, '@')) {
                    $contributor = md5($contributor);
                }
                $url = "https://$service/$contributor.json";
                $clean = function ($x) { return $x->entry[0]; };
            break;
            case 'json':
                $host = parse_url($contributor, PHP_URL_HOST) ?: parse_url('//' . $contributor, PHP_URL_HOST);
                $path = parse_url($contributor, PHP_URL_PATH);
                $url = "https://${host}${path}";
            break;
            // TODO: OpenGraph? via library (e.g. https://github.com/shweshi/OpenGraph) or manual?
            default:
                $host = parse_url($contributor, PHP_URL_HOST) ?: parse_url('//' . $contributor, PHP_URL_HOST);
                $url = "https://$host/.well-known/webfinger?resource=" . urlencode($contributor);
            break;
        }

        try {
            $response = wp_remote_get($url, ['headers' => ['Accept' => 'application/json']]);
            $result = $clean(json_decode($response['body']));
            if (is_wp_error($response) || $response['response']['code'] !== 200 || !$result) {
                return new \WP_Error(
                    'podlove_rest_contributor_lookup_not_found',
                    $response['response']['message'] ?: $response->get_error_message(),
                    ['status' => 404, 'url' => $url]
                );
            }
            return new \WP_REST_Response($result);
        } catch ( \Exception $e ) {
            return new \WP_Error(
                'podlove_rest_contributor_lookup_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
}
