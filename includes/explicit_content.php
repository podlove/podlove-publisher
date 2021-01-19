<?php

add_filter('podlove_episode_form_data', function ($form_data) {
    if (!\Podlove\get_setting('metadata', 'enable_episode_explicit')) {
        return $form_data;
    }

    $form_data[] = [
        'type' => 'select',
        'key' => 'explicit',
        'options' => [
            'label' => __('Explicit Content?', 'podlove-podcasting-plugin-for-wordpress'),
            'type' => 'checkbox',
            'html' => ['style' => 'width: 200px;'],
            'default' => '-1',
            'options' => [0 => 'no', 1 => 'yes', 2 => 'clean'],
        ],
        'position' => 770,
    ];

    return $form_data;
});

add_filter('podlove_episode_data_filter', function ($filter) {
    return array_merge($filter, [
        'explicit' => FILTER_SANITIZE_STRING,
    ]);
});
