<?php

namespace Podlove\Api\Episodes;

use Podlove\Model\Episode;
use Podlove\Model\EpisodeAsset;
use Podlove\Model\MediaFile;
use Podlove\Model\Podcast;
use Podlove\Modules\Seasons;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

add_action('rest_api_init', __NAMESPACE__.'\\api_init');

function api_init()
{
    register_rest_route('podlove/v1', 'episodes', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__.'\\list_api',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('podlove/v1', 'episodes/(?P<id>[\d]+)', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__.'\\episodes_api',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('podlove/v1', 'episodes/(?P<id>[\d]+)', [
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => __NAMESPACE__.'\\episodes_update_api',
        'permission_callback' => __NAMESPACE__.'\\update_episode_permission_check',
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
        return new WP_Error(
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

    return new WP_REST_Response(null, 200);
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

class WP_REST_PodloveEpisode_Controller extends WP_REST_Controller
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
                    'filter' => [
                        'description' => __('The filter parameter is used to filter the collection of episodes', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'enum' => ['publish', 'draft']
                    ]
                ],
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
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
                'methods' => WP_REST_Server::READABLE,
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
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item_media'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'args' => [
                    'asset_id' => [
                        'description' => __('Identifier of the asset.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                        'require' => 'true'
                    ],
                    'enable' => [
                        'description' => __('Is the asset used?', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'boolean',
                        'require' => 'true'
                    ],
                ],
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item_media'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ]
        ]);
    }

    public function get_items_permissions_check($request)
    {
        $filter = $request->get_param('filter');
        if ($filter) {
            if ($filter == 'draft') {
                if (!current_user_can('edit_posts')) {
                    return new \Podlove\Api\Error\ForbiddenAccess();
                }

                return true;
            }

            return true;
        }

        return true;
    }

    public function get_items($request)
    {
        $filter = $request->get_param('filter');
        if (!$filter || $filter != 'draft') {
            $filter = 'publish';
        }

        $episodes = Episode::find_all_by_time([
            'post_status' => $filter,
        ]);

        $results = [];

        foreach ($episodes as $episode) {
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

        $data = [
            '_version' => 'v2',
            'id' => $id,
            'post_id' => $episode->post_id,
            'title' => get_the_title($episode->post_id),
            'title_clean' => $episode->title,
            'subtitle' => trim($episode->subtitle),
            'summary' => trim($episode->summary),
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
            'license_url' => $episode->license_url
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

        $results = array_map(function($asset) use ($episode) {
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
                'enable' => true,
            ];
        }, $assets);

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'results' => $results,
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

        return new WP_REST_Response(null, 500);
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
            $explicit_lowercase = strtolower($explicit);
            if ($explicit_lowercase == 'false') {
                $episode->explicit = 0;
            } elseif ($explicit_lowercase == 'true') {
                $episode->explicit = 1;
            }
        }

        if (isset($request['slug'])) {
            $slug = $request['slug'];
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

        $episode->save();

        if ($isSlugSet) {
            $assets = EpisodeAsset::all();

            foreach ($assets as $asset) {
                $file = MediaFile::find_or_create_by_episode_id_and_episode_asset_id($episode->id, $asset->id);
                $file->determine_file_size();
                $file->save();
            }
        }

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function update_item_media($request)
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

        if (!isset($request['asset_id']) || !isset($request['enable'])) {
            return;
        }

        $asset_id = $request['asset_id'];
        $enable = $request['enable'];

        if (!$enable) {
            $file = MediaFile::find_by_episode_id_and_episode_asset_id($episode->id, $asset_id);
            if ($file) {
                $file->delete();
            }

            return new \Podlove\Api\Response\OkResponse([
                'status' => 'ok'
            ]);
        }

        $file = MediaFile::find_or_create_by_episode_id_and_episode_asset_id($episode->id, $asset_id);
        $file->determine_file_size();
        $file->save();

        if ($file->size == 0) {
            return new \Podlove\Api\Response\OkResponse([
                'message' => 'file size cannot be determined',
                'status' => 'ok'
            ]);
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
