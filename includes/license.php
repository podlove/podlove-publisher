<?php
use Podlove\Model;

if (\Podlove\get_setting('metadata', 'enable_episode_license')) {
    add_filter('podlove_episode_form_data', 'podlove_episode_license_extend_form', 10, 2);
}

function podlove_episode_license_extend_form($form_data, $episode)
{
    $podcast = Model\Podcast::get();
    $license = $episode->get_license();

    $form_data[] = [
        'type' => 'callback',
        'key' => 'podlove_cc_license_selector',
        'options' => [
            'label' => '',
            'callback' => function () {
                ?>
                <div data-client="podlove" style="margin: 15px 0;">
                    <podlove-license></podlove-license>
                </div>
                <?php
            },
        ],
        'position' => 522,
    ];

    return $form_data;
}

