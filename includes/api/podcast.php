<?php

namespace Podlove\Api\Podcast;

use Podlove\Model\Podcast;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

add_action('rest_api_init', function () {
    $controller = new WP_REST_Podlove_Controller();
    $controller->register_routes();
});

class WP_REST_Podlove_Controller extends WP_REST_Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'podcast';
    }

    /**
     * Register the component routes.
     */
    public function register_routes()
    {
        $categories = \Podlove\Itunes\categories(false);
        $categories_enum = [];
        foreach ($categories as $key => $val) {
            array_push( $categories_enum, $val );
        }

        register_rest_route($this->namespace, '/'.$this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
                'args' => [
                    'title' => [
                        'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'subtitle' => [
                        'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'summary' => [
                        'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'author_name' => [
                        'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'mnemonic' => [
                        'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'funding_url' => [
                        'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::url'
                    ],
                    'funding_label' => [
                        'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'copyright' => [
                        'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'expicit' => [
                        'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'boolean',
                    ],
                    'category' => [
                        'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'enum' => $categories_enum,
                    ]
                ]
            ]
        ]);
    }

    /**
     * Check permission for read.
     *
     * @param mixed $request
     */
    public function get_item_permissions_check($request)
    {
        return true;
    }

    /**
     * Check permission for change.
     *
     * @param mixed $request
     */
    public function update_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('sorry, you do not have permissions to use this REST API endpoint'),
                ['status' => 401]
            );
        }

        return true;
    }

    public function get_item($request)
    {
        $podcast = Podcast::get();

        $explicit = false;
        if ($podcast->explicit != 0) {
            $explicit = true;
        }

        $res = [];
        $res['_version'] = 'v2';
        $res['title'] = $podcast->title;
        $res['subtitle'] = $podcast->subtitle;
        $res['summary'] = $podcast->summary;
        $res['mnemonic'] = $podcast->mnemonic;
        $res['itunes_type'] = $podcast->itunes_type;
        $res['author_name'] = $podcast->author_name;
        $res['poster'] = $podcast->cover_art()->setWidth(500)->url();
        $res['link'] = \Podlove\get_landing_page_url();
        $res['funding_url'] = $podcast->funding_url;
        $res['funding_label'] = $podcast->funding_label;
        if (!$podcast->copyright)
            $res['copyright'] = $podcast->default_copyright_claim();
        else
            $res['copyright'] = $podcast->copyright;
        $res['expicit'] = $explicit;
        $res['category'] = self::getCategoryName($podcast->category_1);

        $res = apply_filters('podlove_api_podcast_response', $res);

        return rest_ensure_response($res);
    }

    public function update_item($request)
    {
        $podcast = Podcast::get();
        if (isset($request['title'])) {
            $title = $request['title'];
            $podcast->title = $title;
        }
        if (isset($request['subtitle'])) {
            $subtitle = $request['subtitle'];
            $podcast->subtitle = $subtitle;
        }
        if (isset($request['summary'])) {
            $summary = $request['summary'];
            $podcast->summary = $summary;
        }
        if (isset($request['mnemonic'])) {
            $mnemonic = $request['mnemonic'];
            $podcast->mnemonic = $mnemonic;
        }
        if (isset($request['author_name'])) {
            $author = $request['author_name'];
            $podcast->author_name = $author;
        }
        if (isset($request['funding_url'])) {
            $funding_url = $request['funding_url'];
            $podcast->funding_url = $funding_url;
        }
        if (isset($request['funding_label'])) {
            $funding_label = $request['funding_label'];
            $podcast->funding_label = $funding_label;
        }
        if (isset($request['copyright'])) {
            $copyright = $request['copyright'];
            $podcast->copyright = $copyright;
        }
        if (isset($request['explicit'])) {
            $explicit = $request['explicit'];
            $explicit_lowercase = strtolower($explicit);
            if ($explicit_lowercase == 'false') {
                $podcast->explicit = 0;
            } elseif ($explicit_lowercase == 'true') {
                $podcast->explicit = 1;
            }
        }
        if (isset($request['category'])) {
            $category = $request['category'];
            $category_key = self::getCategoryKey($category);
            $podcast->category_1 = $category_key;
        }


        $podcast->save();

        return new WP_REST_Response(null, 200);
    }

    private static function getCategoryKey($category) {
        $categories = \Podlove\Itunes\categories(false);
        foreach($categories as $key => $val) {
            if ($val == $category)
                return $key;
        }
    }

    private static function getCategoryName($category_key) {
        $categories = \Podlove\Itunes\categories(true);
        foreach($categories as $key => $val) {
            if ($key == $category_key)
                return $val;
        }
    }
}
