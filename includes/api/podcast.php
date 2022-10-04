<?php

namespace Podlove\Api\Podcast;

use Podlove\Model\Podcast;
use WP_REST_Controller;
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
                        'description' => __('Title of the podcast', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'subtitle' => [
                        'description' => __('Extension to the title. Clarify what the podcast is about.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'summary' => [
                        'description' => __('Elaborate description of the podcasts content.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'author_name' => [
                        'description' => __('Name of the podcast author. Publicly displayed in Podcast directories.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'podcast_email' => [
                        'description' => __('Used by iTunes and other Podcast directories to contact you.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'validate_callback' => 'is_email',
                    ],
                    'mnemonic' => [
                        'description' => __('Abbreviation for your podcast.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'funding_url' => [
                        'description' => __('Can be used by podcatchers show funding/donation links for the podcast.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::url'
                    ],
                    'funding_label' => [
                        'description' => __('Label for funding/donation URL.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'copyright' => [
                        'description' => __('Copyright notice for content in the channel.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'expicit' => [
                        'description' => __('Is the overall content of the podcast explicit?', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'boolean',
                    ],
                    'category' => [
                        'description' => __('iTunes category of the podcast', 'podlove-podcasting-plugin-for-wordpress'),
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
            return new \Podlove\Api\Error\ForbiddenAccess();
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
        $res['podcast_email'] = $podcast->owner_email;
        $res['poster'] = $podcast->cover_art()->setWidth(500)->url();
        $res['link'] = \Podlove\get_landing_page_url();
        $res['funding_url'] = $podcast->funding_url;
        $res['funding_label'] = $podcast->funding_label;
        if (!$podcast->copyright)
            $res['copyright'] = $podcast->default_copyright_claim();
        else
            $res['copyright'] = $podcast->copyright;
        $res['expicit'] = $explicit;
        $res['category'] = $this->getCategoryName($podcast->category_1);

        $res = apply_filters('podlove_api_podcast_response', $res);

        return new \Podlove\Api\Response\OkResponse($res);
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
        if (isset($request['podcast_email'])) {
            $podcast_email = $request['podcast_email'];
            $podcast->owner_email = $podcast_email;
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
            $category_key = $this->getCategoryKey($category);
            $podcast->category_1 = $category_key;
        }

        $podcast->save();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    private function getCategoryKey($category) 
    {
        $categories = \Podlove\Itunes\categories(false);
        foreach($categories as $key => $val) {
            if ($val == $category) {
                return $key;
            }
        }
    }

    private function getCategoryName($category_key) 
    {
        $categories = \Podlove\Itunes\categories(true);
        foreach($categories as $key => $val) {
            if ($key == $category_key) {
                return $val;
            }
        }
    }
}
