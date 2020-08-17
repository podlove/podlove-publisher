<?php

namespace Podlove\Api\Episodes;

use Podlove\Model\Episode;
use Podlove\Modules\PodloveWebPlayer\PlayerV3\PlayerMediaFiles;

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
    $post = get_post($episode->post_id);

    return new \WP_REST_Response([
        '_version' => 'v1',
        'title' => $post->post_title,
        'subtitle' => trim($episode->subtitle),
        'summary' => trim($episode->summary),
        'publicationDate' => mysql2date('c', $post->post_date),
        'duration' => $episode->get_duration('full'),
        'poster' => $episode->cover_art_with_fallback()->setWidth(500)->url(),
        'link' => get_permalink($episode->post_id),
        'chapters' => chapters($episode),
        'audio' => media_files($episode),
        // @todo: all media files
    ]);
}

function chapters($episode = null)
{
    return array_map(function ($c) {
        $c->title = html_entity_decode(trim($c->title));

        return $c;
    }, (array) json_decode($episode->get_chapters('json')));
}

function media_files($episode = null)
{
    $player_media_files = new PlayerMediaFiles($episode);

    if ($media_files = $player_media_files->get()) {
        return array_map(function ($file) {
            return [
                'url' => $file['publicUrl'],
                'size' => $file['size'],
                'title' => $file['assetTitle'],
                'mimeType' => $file['mime_type'],
            ];
        }, $media_files);
    }

    return [];
}
