<?php

namespace Podlove\Api\Show;

use Podlove\Model\Podcast;

add_action('rest_api_init', __NAMESPACE__.'\\api_init');

function api_init()
{
    // FIXME: why is this called "show", not "podcast"? 🤔
    register_rest_route('podlove/v1', 'show', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__.'\\show_api',
        'permission_callback' => '__return_true',
    ]);
}

function show_api()
{
    $podcast = Podcast::get();

    $response = [
        '_version' => 'v1',
        'title' => $podcast->title,
        'subtitle' => $podcast->subtitle,
        'summary' => $podcast->summary,
        'mnemonic' => $podcast->mnemonic,
        'itunes_type' => $podcast->itunes_type,
        'author_name' => $podcast->author_name,
        'poster' => $podcast->cover_art()->setWidth(500)->url(),
        'link' => \Podlove\get_landing_page_url(),
    ];

    $response = apply_filters('podlove_api_podcast_response', $response);

    return new \WP_REST_Response($response);
}
