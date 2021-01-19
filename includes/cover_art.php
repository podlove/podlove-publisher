<?php

use Podlove\Model;

// extend episode form
add_filter('podlove_episode_form_data', function ($form_data, $episode) {
    if (Model\AssetAssignment::get_instance()->image !== 'manual') {
        return $form_data;
    }

    $form_data[] = [
        'type' => 'upload',
        'key' => 'cover_art',
        'options' => [
            'label' => __('Episode Image', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => __('Enter URL or select image from media library.', 'podlove-podcasting-plugin-for-wordpress')
                .' '
                .__('Apple/iTunes recommends 3000 x 3000 pixel JPG or PNG', 'podlove-podcasting-plugin-for-wordpress'),
            'html' => ['class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'url'],
        ],
        'position' => 790,
    ];

    return $form_data;
}, 10, 2);

add_filter('podlove_episode_data_filter', function ($filter) {
    return array_merge($filter, [
        'cover_art' => FILTER_SANITIZE_STRING,
    ]);
});
