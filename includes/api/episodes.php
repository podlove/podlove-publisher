<?php

namespace Podlove\Api\Episodes;

use Podlove\Model\Episode;
use Podlove\Model\EpisodeAsset;
use Podlove\Model\MediaFile;
use Podlove\Model\Podcast;
use Podlove\Modules\Seasons;
use Podlove\Modules\Shows;

add_action('rest_api_init', __NAMESPACE__.'\api_init');

function api_init()
{
    register_rest_route('podlove/v1', 'episodes', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__.'\list_api',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('podlove/v1', 'episodes/(?P<id>[\d]+)', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__.'\episodes_api',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('podlove/v1', 'episodes/(?P<id>[\d]+)', [
        'methods' => \WP_REST_Server::EDITABLE,
        'callback' => __NAMESPACE__.'\episodes_update_api',
        'permission_callback' => __NAMESPACE__.'\update_episode_permission_check',
    ]);
}

function list_api()
{
    $episodes = Episode::find_all_by_time([
        'post_status' => 'publish',
    ]);

    $results = [];

    foreach ($episodes as $episode) {
        array_push($results, [
            'id' => $episode->id,
            'title' => get_the_title($episode->post_id),
        ]);
    }

    return new \WP_REST_Response([
        'results' => $results,
        '_version' => 'v1',
    ]);
}

function episodes_api($request)
{
    $id = $request->get_param('id');
    $episode = Episode::find_by_id($id);
    $podcast = Podcast::get();
    $post = get_post($episode->post_id);

    return new \WP_REST_Response([
        '_version' => 'v1',
        'id' => $id,
        'slug' => $post->post_name,
        'title' => get_the_title($episode->post_id),
        'title_clean' => $episode->title,
        'subtitle' => trim($episode->subtitle),
        'summary' => trim($episode->summary),
        'publicationDate' => mysql2date('c', $post->post_date),
        'duration' => $episode->get_duration('full'),
        'poster' => $episode->cover_art_with_fallback()->setWidth(500)->url(),
        'link' => get_permalink($episode->post_id),
        'chapters' => chapters($episode),
        'audio' => \podlove_pwp5_audio_files($episode, null),
        'files' => \podlove_pwp5_files($episode, null),
        'content' => apply_filters('the_content', $post->post_content),
        'number' => $episode->number,
        'mnemonic' => $podcast->mnemonic.($episode->number < 100 ? '0' : '').($episode->number < 10 ? '0' : '').$episode->number,
        'soundbite_start' => $episode->soundbite_start,
        'soundbite_duration' => $episode->soundbite_duration,
        'soundbite_title' => $episode->soundbite_title
        // @todo: all media files
    ]);
}

/**
 * Check permission for change.
 *
 * @param mixed $request
 */
function update_episode_permission_check($request)
{
    if (!current_user_can('edit_posts')) {
        return new \WP_Error(
            'rest_forbidden',
            esc_html__('sorry, you do not have permissions to use this REST API endpoint'),
            ['status' => 401]
        );
    }

    return true;
}

function episodes_update_api($request)
{
    $id = $request->get_param('id');
    $episode = Episode::find_by_id($id);

    if (!$episode) {
        return;
    }

    if (isset($request['soundbite_start'])) {
        $start = $request['soundbite_start'];
        if (preg_match('/\d\d:[0-5]\d:[0-5]\d?.?\d?\d?\d/', $start)) {
            $episode->soundbite_start = $start;
        } else {
            return;
        }
    }

    if (isset($request['soundbite_duration'])) {
        $duration = $request['soundbite_duration'];
        if (preg_match('/\d\d:[0-5]\d:[0-5]\d?.?\d?\d?\d/', $duration)) {
            $episode->soundbite_duration = $duration;
        } else {
            return;
        }
    }

    if (isset($request['soundbite_title'])) {
        $title = $request['soundbite_title'];
        $episode->soundbite_title = $title;
    }

    $episode->save();

    return new \WP_REST_Response(null, 200);
}

function chapters($episode = null)
{
    return array_map(function ($c) {
        $c->title = html_entity_decode(trim($c->title));

        return $c;
    }, (array) json_decode($episode->get_chapters('json')));
}

add_action('rest_api_init', function () {
    $controller = new WP_REST_PodloveEpisode_Controller();
    $controller->register_routes();
});

class WP_REST_PodloveEpisode_Controller extends \WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'episodes';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base, [
            [
                'args' => [
                    'status' => [
                        'description' => __('The status parameter is used to filter the collection of episodes', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'enum' => ['publish', 'draft', 'all']
                    ],
                    'show' => [
                        'description' => __('Filter by show slug.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string'
                    ],
                    'sort_by' => [
                        'description' => __('Sort the list of episodes', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'enum' => ['post_id', 'post_date']
                    ],
                    'order_by' => [
                        'description' => __('Ascending or descending order for sorting of the list of episodes', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'enum' => ['asc', 'desc', 'ASC', 'DESC']
                    ]
                ],
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)/build_slug', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
                'title' => [
                    'type' => 'string'
                ]
            ],
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'build_slug'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
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
                    'title' => [
                        'description' => __('Clear, concise name for your episode.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'subtitle' => [
                        'description' => __('Single sentence describing the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'summary' => [
                        'description' => __('A summary of the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'number' => [
                        'description' => __('An epsiode number.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                    ],
                    'slug' => [
                        'description' => __('Episode media file slug.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'duration' => [
                        'description' => __('Duration of the episode', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::timestamp'
                    ],
                    'type' => [
                        'description' => __('Episode type. May be used by podcast clients.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'enum' => ['full', 'trailer', 'bonus']
                    ],
                    'cover' => [
                        'description' => __('An url for the episode cover', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::episodeCover'
                    ],
                    'explicit' => [
                        'description' => __('explicit content?', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'boolean'
                    ],
                    'soundbite_start' => [
                        'description' => __('Start value of podcast:soundbite tag', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::timestamp'
                    ],
                    'soundbite_duration' => [
                        'description' => __('Duration value of podcast::soundbite tag', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::timestamp'
                    ],
                    'soundbite_title' => [
                        'description' => __('Title for the podcast::soundbite tag', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string'
                    ],
                    'auphonic_production_id' => [
                        'description' => 'Auphonic Production ID',
                        'type' => 'string'
                    ],
                    'is_auphonic_production_running' => [
                        'description' => 'Tracks if Auphonic production is running',
                        'type' => 'boolean'
                    ],
                    'auphonic_webhook_config' => [
                        'description' => 'Auphonic Webhook after Production is done',
                        'type' => 'object',
                        'properties' => [
                            'authkey' => [
                                'description' => 'Authentication key',
                                'type' => 'string',
                                'required' => 'true'
                            ],
                            'enabled' => [
                                'description' => 'Publish episode when Production is done?',
                                'type' => 'boolean',
                                'required' => 'true'
                            ]
                        ]
                    ],
                    'show' => [
                        'description' => 'Show slug. Assigns episode to given show.',
                        'type' => 'string'
                    ],
                    'skip_validation' => [
                        'description' => 'If true, mediafile validation is skipped on slug change.',
                        'type' => 'boolean',
                    ]
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

        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)/media/(?P<asset_id>[\d]+)/enable', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
                'asset_id' => [
                    'description' => __('Unique identifier for the asset.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item_media_enable'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)/media/(?P<asset_id>[\d]+)/disable', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
                'asset_id' => [
                    'description' => __('Unique identifier for the asset.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item_media_disable'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)/media/(?P<asset_id>[\d]+)/verify', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
                'asset_id' => [
                    'description' => __('Unique identifier for the asset.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item_media_verify'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)/media', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'args' => [
                    'asset_id' => [
                        'description' => __('Identifier of the asset.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                    ],
                    'asset' => [
                        'description' => __('Name of the asset.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'file_url' => [
                        'description' => __('File url for the asset', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'enable' => [
                        'description' => __('Is the asset used?', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'boolean',
                    ],
                ],
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item_media'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ]
        ]);
        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)/tags', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                ],
            ],
            [
                'args' => [
                    'term_id' => [
                        'description' => __('Identifier of the term', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                    ],
                    'name' => [
                        'description' => __('Name of the term', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item_tags'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item_tags'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item_tags'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ]
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

    public function get_items($request)
    {
        $filter = $request->get_param('status');
        if (!$filter || ($filter != 'draft' && $filter != 'all')) {
            $filter = 'publish';
        }

        $order_by = $request->get_param('order_by');
        $sort_by = $request->get_param('sort_by');

        $args = [];
        if ($order_by) {
            $args['order_by'] = $order_by;
        }

        if ($sort_by) {
            if ($sort_by == 'post_id') {
                $args['sort_by'] = 'ID';
            } else {
                $args['sort_by'] = $sort_by;
            }
        }

        if ($filter != 'all') {
            $args['post_status'] = $filter;
        }

        $show_slug = $request->get_param('show');
        if ($show_slug) {
            $show = Shows\Model\Show::find_one_term_by_property('slug', $show_slug);
            if (!$show) {
                return new \Podlove\Api\Error\NotFound('rest_not_found', 'There is no show with slug "'.$show_slug.'".');
            }
        }

        $episodes = Episode::find_all_by_time($args);

        $results = [];

        foreach ($episodes as $episode) {
            // filter by show slug
            if ($show_slug) {
                $show = Shows\Model\Show::find_one_by_episode_id($episode->id);
                if (!$show || $show_slug != $show->slug) {
                    continue;
                }
            }

            array_push($results, [
                'id' => $episode->id,
                'title' => get_the_title($episode->post_id),
            ]);
        }

        return new \Podlove\Api\Response\OkResponse([
            'results' => $results,
            '_version' => 'v2',
        ]);
    }

    public function get_item_permissions_check($request)
    {
        $id = $request->get_param('id');
        $episode = Episode::find_by_id($id);
        if (!$episode) {
            return false;
        }

        $post = $episode->post();
        if (!$post) {
            return false;
        }

        if ($post->post_status == 'publish' && $post->post_type == 'podcast') {
            return true;
        }

        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function get_item($request)
    {
        $id = $request->get_param('id');
        $episode = Episode::find_by_id($id);
        $podcast = Podcast::get();
        $post = get_post($episode->post_id);
        $explicit = false;
        if ($episode->explicit != 0) {
            $explicit = true;
        }

        $postterms = get_the_terms($episode->post_id, 'shows');
        $show = (is_array($postterms) && isset($postterms[0]) ? $postterms[0]->slug : '');

        $data = [
            '_version' => 'v2',
            'id' => $id,
            'slug' => $episode->slug,
            'post_id' => $episode->post_id,
            'title' => get_the_title($episode->post_id),
            'title_clean' => $episode->title,
            'subtitle' => trim($episode->subtitle ?? ''),
            'summary' => trim($episode->summary ?? ''),
            'duration' => $episode->get_duration('full'),
            'type' => $episode->type,
            'publicationDate' => mysql2date('c', $post->post_date),
            'recording_date' => $episode->recording_date,
            'poster' => $episode->cover_art_with_fallback()->setWidth(500)->url(),
            'episode_poster' => $episode->cover,
            'link' => get_permalink($episode->post_id),
            'audio' => \podlove_pwp5_audio_files($episode, null),
            'files' => \podlove_pwp5_files($episode, null),
            'number' => $episode->number,
            'mnemonic' => $podcast->mnemonic.($episode->number < 100 ? '0' : '').($episode->number < 10 ? '0' : '').$episode->number,
            'soundbite_start' => $episode->soundbite_start,
            'soundbite_duration' => $episode->soundbite_duration,
            'soundbite_title' => $episode->soundbite_title,
            'explicit' => $explicit,
            'license_name' => $episode->license_name,
            'license_url' => $episode->license_url,
            'auphonic_production_id' => get_post_meta($episode->post_id, 'auphonic_production_id', true),
            'is_auphonic_production_running' => get_post_meta($episode->post_id, 'is_auphonic_production_running', true),
            'show' => $show
        ];

        $data = $this->enrich_with_season($data, $episode);

        return new \Podlove\Api\Response\OkResponse($data);
    }

    public function get_item_media($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        $assets = EpisodeAsset::all();

        $results = array_map(function ($asset) use ($episode) {
            $file = MediaFile::find_by_episode_id_and_episode_asset_id($episode->id, $asset->id);

            return !$file ? [
                'asset_id' => $asset->id,
                'asset' => $asset->title,
                'enable' => false,
            ] : [
                'asset_id' => $asset->id,
                'asset' => $asset->title,
                'url' => $file->get_file_url(),
                'size' => $file->size,
                'enable' => (bool) $file->active,
            ];
        }, $assets);

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'results' => $results,
        ]);
    }

    public function get_item_tags($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);
        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        $post_id = $episode->post_id;

        $post_tag_terms = wp_get_object_terms($post_id, 'post_tag');
        if (!empty($post_tag_terms) && !is_wp_error($post_tag_terms)) {
            $results = array_map(function ($tags) {
                return [
                    'term_id' => $tags->term_id,
                    'name' => $tags->name
                ];
            }, $post_tag_terms);

            return new \Podlove\Api\Response\OkResponse([
                '_version' => 'v2',
                'tags' => $results
            ]);
        }

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok',
            'tags' => []
        ]);
    }

    public function create_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function create_item($request)
    {
        // create a post (only as draft)
        $new_post = [
            'post_title' => 'API created Podcast-Post',
            'post_type' => 'podcast',
            'post_status' => 'draft'
        ];
        $post_id = wp_insert_post($new_post);
        if ($post_id) {
            // create an episode with the created post
            $episode = Episode::find_or_create_by_post_id($post_id);
            $url = sprintf('%s/%s/%d', $this->namespace, $this->rest_base, $episode->id);
            $message = sprintf('Episode successfully created with id %d', $episode->id);
            $data = [
                'message' => $message,
                'location' => $url,
                'id' => $episode->id
            ];
            $headers = [
                'location' => $url
            ];

            return new \Podlove\Api\Response\CreateResponse($data, $headers);
        }

        return new \WP_REST_Response(null, 500);
    }

    public function build_slug($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);
        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        $title = $request->get_param('title') ?? get_the_title($episode->post_id);

        $slug = sanitize_title($title);

        return new \Podlove\Api\Response\CreateResponse(['slug' => $slug]);
    }

    public function update_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function update_item($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);
        $isSlugSet = false;

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        if (isset($request['title'])) {
            $title = $request['title'];
            $episode->title = $title;
            $post_update = [
                'ID' => $episode->post_id,
                'post_title' => $title
            ];
            wp_update_post($post_update);
        }

        if (isset($request['subtitle'])) {
            $subtitle = $request['subtitle'];
            $episode->subtitle = $subtitle;
        }

        if (isset($request['summary'])) {
            $summary = $request['summary'];
            $episode->summary = $summary;
        }

        if (isset($request['number'])) {
            $number = $request['number'];
            $episode->number = $number;
        }

        if (isset($request['explicit'])) {
            $explicit = $request['explicit'];
            if (is_string($explicit)) {
                $explicit_lowercase = strtolower($explicit);
                if ($explicit_lowercase == 'true') {
                    $episode->explicit = 1;
                } elseif ($explicit_lowercase == 'false') {
                    $episode->explicit = 0;
                }
            } else {
                if ($explicit) {
                    $episode->explicit = 1;
                } else {
                    $episode->explicit = 0;
                }
            }
        }

        if (isset($request['slug'])) {
            $slug = trim($request['slug']);
            $episode->slug = $slug;
            $isSlugSet = true;
        }

        if (isset($request['duration'])) {
            $duration = $request['duration'];
            $episode->duration = $duration;
        }

        if (isset($request['recording_date'])) {
            $recording_date = $request['recording_date'];
            $episode->recording_date = $recording_date;
        }

        if (isset($request['type'])) {
            $type = $request['type'];
            $episode->type = $type;
        }

        if (isset($request['episode_poster'])) {
            $episode_poster = $request['episode_poster'];
            $episode->cover_art = $episode_poster;
        }

        if (isset($request['soundbite_start'])) {
            $start = $request['soundbite_start'];
            $episode->soundbite_start = $start;
        }

        if (isset($request['soundbite_duration'])) {
            $duration = $request['soundbite_duration'];
            $episode->soundbite_duration = $duration;
        }

        if (isset($request['soundbite_title'])) {
            $title = $request['soundbite_title'];
            $episode->soundbite_title = $title;
        }

        if (isset($request['license_name'])) {
            $license_name = $request['license_name'];
            $episode->license_name = $license_name;
        }

        if (isset($request['license_url'])) {
            $license_url = $request['license_url'];
            $episode->license_url = $license_url;
        }

        if (isset($request['auphonic_production_id'])) {
            update_post_meta($episode->post_id, 'auphonic_production_id', $request['auphonic_production_id']);
        }

        if (isset($request['is_auphonic_production_running'])) {
            update_post_meta($episode->post_id, 'is_auphonic_production_running', $request['is_auphonic_production_running']);
        }

        if (isset($request['show'])) {
            Shows\Shows::set_show_for_episode($episode->post_id, $request['show']);
        }

        $episode->save();

        // DEPRECATED: clients should validate themselves. Remove in v3.
        if ($isSlugSet && !$request['skip_validation']) {
            $assets = EpisodeAsset::all();

            foreach ($assets as $asset) {
                $file = MediaFile::find_or_create_by_episode_id_and_episode_asset_id($episode->id, $asset->id);
                $file->determine_file_size();
                $file->save(false);
            }
        }

        \podlove_clear_feed_cache_for_post($episode->post_id);

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function update_item_media_enable($request)
    {
        $asset_id = $request['asset_id'];
        $episode = $this->get_episode_from_request($request);

        if (is_wp_error($episode)) {
            return $episode;
        }

        $file = MediaFile::find_or_create_by_episode_id_and_episode_asset_id($episode->id, $asset_id);
        $file->determine_file_size();
        $file->active = true;
        $file->save();

        if ($file->size == 0) {
            return new \Podlove\Api\Response\OkResponse([
                'message' => 'file size cannot be determined',
                'active' => $file->active,
                'status' => 'ok'
            ]);
        }
        do_action('podlove_media_file_content_verified', $file->id);

        \podlove_clear_feed_cache_for_post($episode->post_id);

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok',
            'file_size' => $file->size,
            'file_url' => $file->get_file_url(),
            'active' => $file->active
        ]);
    }

    public function update_item_media_disable($request)
    {
        $asset_id = $request['asset_id'];
        $episode = $this->get_episode_from_request($request);

        if (is_wp_error($episode)) {
            return $episode;
        }

        $file = MediaFile::find_by_episode_id_and_episode_asset_id($episode->id, $asset_id);
        if ($file) {
            $file->active = false;
            $file->save();
        }

        \podlove_clear_feed_cache_for_post($episode->post_id);

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok',
            'file_size' => $file->size,
            'file_url' => $file->get_file_url(),
            'active' => $file->active,
        ]);
    }

    public function update_item_media_verify($request)
    {
        $asset_id = $request['asset_id'];
        $episode = $this->get_episode_from_request($request);

        if (is_wp_error($episode)) {
            return $episode;
        }

        $file = MediaFile::find_or_create_by_episode_id_and_episode_asset_id($episode->id, $asset_id);
        $file->determine_file_size();
        $file->save(false);

        if ($file->size == 0) {
            return new \Podlove\Api\Response\OkResponse([
                'status' => 'ok',
                'message' => 'file size cannot be determined',
                'file_url' => $file->get_file_url(),
                'active' => $file->active,
            ]);
        }
        do_action('podlove_media_file_content_verified', $file->id);

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok',
            'file_size' => $file->size,
            'file_url' => $file->get_file_url(),
            'active' => $file->active,
        ]);
    }

    public function update_item_tags($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);
        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        $post_id = $episode->post_id;

        if (isset($request['term_id'])) {
            $terms = $request['term_id'];
            if (is_array($terms)) {
                $term_ids = array_map(function ($term) {
                    return intval($term);
                }, $terms);
            } else {
                $term_ids = intval($terms);
            }
            $val = wp_set_object_terms($post_id, $term_ids, 'post_tag', true);
            if (is_wp_error($val)) {
                return new \Podlove\Api\Error\InternalServerError(500, $val->message);
            }
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

        wp_trash_post($episode->post_id);

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function delete_item_tags($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);
        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        $post_id = $episode->post_id;

        $val = wp_set_object_terms($post_id, [], 'post_tag', false);

        if (is_wp_error($val)) {
            return new \Podlove\Api\Error\InternalServerError();
        }

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    private function get_episode_from_request($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return new \Podlove\Api\Error\NotFound();
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        return $episode;
    }

    private function enrich_with_season($data, Episode $episode)
    {
        if (!\Podlove\Modules\Base::is_active('seasons')) {
            return $data;
        }

        $season = Seasons\Model\Season::for_episode($episode);
        if (!$season) {
            return $data;
        }

        $data['season_id'] = (int) $season->id;

        return $data;
    }
}
