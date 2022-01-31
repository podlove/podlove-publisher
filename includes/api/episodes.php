<?php

namespace Podlove\Api\Episodes;

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Modules\Contributors\Model\EpisodeContribution;
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
            'title' => $episode->title,
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
        'title' => $post->post_title,
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
                        'description' => __('The filter parameter is used to filter the collection of episodes'),
                        'type' => 'string',
                        'enum' => array('publish', 'draft')
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
                    'description' => __('Unique identifier for the episode.'),
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
                        'description' => __('Clear, concise name for your episode.'),
                        'type' => 'string',
                    ],
                    'subtitle' => [
                        'description' => __('Single sentence describing the episode..'),
                        'type' => 'string',
                    ],
                    'summary' => [
                        'description' => __('A summary of the episode.'),
                        'type' => 'string',
                    ],
                    'number' => [
                        'description' => __('An epsiode number.'),
                        'type' => 'integer',
                    ],
                    'slug' => [
                        'description' => __('Episode media file slug.'),
                        'type' => 'string',
                    ],
                    'explicit' => [
                        'description' => __('explicit content?'),
                        'type' => 'string',
                        'enum' => array('yes', 'no')
                    ],
                    'soundbite_start' => [
                        'description' => __('Start value of podcast:soundbite tag'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::timestamp'
                    ],
                    'soundbite_duration' => [
                        'description' => __('Duration value of podcast::soundbite tag'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::timestamp'
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

    }

    public function get_items_permissions_check( $request )
    {
        $filter = $request->get_param('filter');
        if ($filter) {
            if ($filter == 'draft') {
                if (!current_user_can('edit_posts')) {
                    return new \Podlove\Api\Error\ForbiddenAccess();
                }
                return true;
            }
            else {
                return true;
            }
        }
        else {
            return true;
        }
    }

    public function get_items( $request )
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
                'title' => $episode->title,
            ]);
        }

        return new \Podlove\Api\Response\OkResponse([
            'results' => $results,
            '_version' => 'v2',
        ]);
    
    }

    public function get_item_permissions_check( $request )
    {
        return true;
    }

    public function get_item( $request )
    {
        $id = $request->get_param('id');
        $episode = Episode::find_by_id($id);
        $podcast = Podcast::get();
        $explicit = false;
        if ($episode->explicit != 0)
            $explicit = true;
        
        $data = [
            '_version' => 'v2',
            'id' => $id,
            'post_id' => $episode->post_id,
            'title' => $episode->title,
            'subtitle' => trim($episode->subtitle),
            'summary' => trim($episode->summary),
            'duration' => $episode->get_duration('full'),
            'poster' => $episode->cover_art_with_fallback()->setWidth(500)->url(),
            'link' => get_permalink($episode->post_id),
            'audio' => \podlove_pwp5_audio_files($episode, null),
            'files' => \podlove_pwp5_files($episode, null),
            'number' => $episode->number,
            'mnemonic' => $podcast->mnemonic.($episode->number < 100 ? '0' : '').($episode->number < 10 ? '0' : '').$episode->number,
            'soundbite_start' => $episode->soundbite_start,
            'soundbite_duration' => $episode->soundbite_duration,
            'explicit' => $explicit
        ];

        return new \Podlove\Api\Response\OkResponse($data);
    
    }

    public function create_item_permissions_check( $request )
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }
        return true;
    }

    public function create_item( $request )
    {
        // create a post (only as draft)
        $new_post = array(
            'post_title' => 'API created Podcast-Post',
            'post_type' => 'podcast',
            'post_status' => 'draft'
        );
        $post_id = wp_insert_post( $new_post );
        if ( $post_id ) {
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
        else {
            return new WP_REST_Response(null, 500);
        }
        
    }

    public function update_item_permissions_check( $request )
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function update_item( $request )
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

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
            if ($explicit_lowercase == 'no')
                $episode->explicit = 0;
            else if ($explicit_lowercase == 'yes')
                $episode->explicit = 1;
        }

        if (isset($request['slug'])) {
            $slug = $request['slug'];
            $episode->slug = $slug;
        }

        if (isset($request['soundbite_start'])) {
            $start = $request['soundbite_start'];
            $episode->soundbite_start = $start;
        }

        if (isset($request['soundbite_duration'])) {
            $duration = $request['soundbite_duration'];
            $episode->soundbite_duration = $duration;
        }

        $episode->save();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function delete_item_permissions_check( $request )
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function delete_item( $request )
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
}

class WP_REST_PodloveEpisodeContributor_Controller extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'episodes';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/contributors', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.'),
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
                    'contributors' => [
                        'description' => __('List of contributors of the episode'),
                        'type' => 'array',
                        'items' => array(
                            'type' => 'object',
                            'properties' => array(
                                'contributor_id' => array(
                                    'description' => __('Id of a contributor'),
                                    'type' => 'integer',
                                    'required' => 'true'
                                ),
                                'group_id' => array(
                                    'description' => __('Id of group of the contributor'),
                                    'type' => 'integer',

                                ),
                                'role_id' => array(
                                    'description' => __('Id of role of the contributor'),
                                    'type' => 'integer',

                                ),
                                'comment' => array(
                                    'description' => __('Comment to the contributor'),
                                    'type' => 'string'
                                )
                            )
                        )
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
    }

    public function get_item($request)
    {
        $id = $request->get_param('id');

        $results = array_map(function ($contributor) {
            return [
                'contributor_id' => $contributor->contributor_id,
                'role_id' => $contributor->role_id,
                'group_id' => $contributor->group_id,
                'comment' => $contributor->comment,
            ];
        }, EpisodeContribution::find_all_by_episode_id($id));

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'contributors' => $results
        ]);
    }

    public function get_item_permissions_check($request)
    {
        return true;
    }

    public function update_item($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        if (isset($request['contributors']) && is_array($request['contributors'])) {
            for ($i = 0; $i < count($request['contributors']); ++$i ) {
                $contrib = new EpisodeContribution();
                $contrib->episode_id = $id;
                    if (isset($request['contributors'][$i]['contributor_id'])) {
                    $contrib->contributor_id = $request['contributors'][$i]['contributor_id'];
                }
                if (isset($request['contributors'][$i]['group_id'])) {
                    $contrib->group = $request['contributors'][$i]['group_id'];
                }
                if (isset($request['contributors'][$i]['role_id'])) {
                    $contrib->role = $request['contributors'][$i]['role_id'];
                }
                if (isset($request['contributors'][$i]['comment'])) {
                    $contrib->comment = $request['contributors'][$i]['comment'];
                }
                $contrib->save();
            }
        }

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
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        $contributors = EpisodeContribution::find_all_by_episode_id($id);

        foreach ($contributors as $contributor) {
            $contributor->delete();
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

}