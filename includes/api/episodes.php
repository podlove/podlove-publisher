<?php

namespace Podlove\Api\Episodes;

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
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
        'soundbite_duration' => $episode->soundbite_duration
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
        $this->namespace = 'podlove/v1';
        $this->rest_base = 'episode';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_episodes'],
                'permission_callback' => [$this, 'get_episodes_permission_check'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_episode'],
                'permission_callback' => [$this, 'create_episode_permission_check'],

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
                'callback' => [$this, 'get_episode'],
                'permission_callback' => [$this, 'get_episode_permission_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_episode'],
                'permission_callback' => [$this, 'update_episode_permission_check'],

            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_episode'],
                'permission_callback' => [$this, 'delete_episode_permission_check'],

            ]
        ]);

    }

    public function get_episodes_permission_check()
    {
        return true;
    }

    public function get_episode_permission_check()
    {
        return true;
    }

    public function create_episode_permission_check()
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

    public function update_episode_permission_check()
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
    public function delete_episode_permission_check()
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

    public function get_episodes()
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

    public function create_episode($request)
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
            $response = new WP_REST_Response(null, 201);
            $response->header('Location', rest_url($url));
            return $response;
        }
        else {
            return new WP_REST_Response(null, 500);
        }
        
    }

    public function get_episode($request)
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
            'soundbite_duration' => $episode->soundbite_duration
            // @todo: all media files
        ]);
    
    }

    public function update_episode($request)
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
    
        $episode->save();
    
        return new WP_REST_Response(null, 200);
    }

    public function delete_episode($request)
    {

    }
}