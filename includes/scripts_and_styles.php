<?php

function podlove_client_script_attributes($attributes)
{
    if (($attributes['id'] ?? '') === 'podlove-vue-app-client-js') {
        $attributes['type'] = 'module';
        $attributes['crossorigin'] = 'anonymous';
    }

    return $attributes;
}

// Preserve WordPress's inline translations and data when making the client a module.
add_filter('wp_script_attributes', 'podlove_client_script_attributes');

// admin styles & scripts
add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    $screen_base = $screen ? $screen->base : '';

    $is_episode_edit_screen = \Podlove\is_episode_edit_screen();
    $is_podlove_dashboard_screen = isset(\Podlove\Settings\Dashboard::$pagehook)
        && $screen
        && in_array(\Podlove\Settings\Dashboard::$pagehook, [$screen->id, $screen_base], true);

    $version = \Podlove\get_plugin_header('Version');

    $vue_screens = [
        'podlove_page_podlove_slackshownotes_settings',
        'podlove_page_podlove_tools_settings_handle',
        'podlove_page_podlove_analytics',
        'podlove-setup-wizard',
        'podlove_page_publisher_plus_settings',
    ];

    $needs_vue_client = $is_episode_edit_screen
        || in_array($screen_base, $vue_screens, true)
        || $is_podlove_dashboard_screen;
    $needs_legacy_vue_apps = $is_episode_edit_screen || in_array($screen_base, $vue_screens, true);

    if ($needs_vue_client) {
        wp_register_script(
            'podlove-vue-app-client',
            \Podlove\PLUGIN_URL.'/client/dist/client.js',
            ['wp-i18n'],
            $version,
            false
        );
        wp_enqueue_style('podlove-vue-app-client-css', \Podlove\PLUGIN_URL.'/client/dist/style.css', [], $version);

        add_filter('podlove_data_js', function ($data) {
            $data['api'] = [
                'base' => esc_url_raw(rest_url('podlove')),
                'nonce' => wp_create_nonce('wp_rest'),
            ];

            return $data;
        });

        wp_set_script_translations('podlove-vue-app-client', 'podlove-podcasting-plugin-for-wordpress');

        if ($needs_legacy_vue_apps) {
            wp_register_script(
                'podlove-episode-vue-apps',
                \Podlove\PLUGIN_URL.'/js/dist/app.js',
                ['underscore', 'jquery', 'wp-i18n'],
                $version,
                true
            );

            wp_set_script_translations('podlove-episode-vue-apps', 'podlove-podcasting-plugin-for-wordpress');

            $episode = Podlove\Model\Episode::find_or_create_by_post_id(get_the_ID());

            if (!$episode) {
                wp_localize_script(
                    'podlove-episode-vue-apps',
                    'podlove_vue',
                    [
                        'rest_url' => esc_url_raw(rest_url()),
                        'nonce' => wp_create_nonce('wp_rest'),
                        'post_id' => get_the_ID(),
                        'episode_id' => 0,
                        'osf_active' => is_plugin_active('shownotes/shownotes.php'),
                    ]
                );
            } else {
                wp_localize_script(
                    'podlove-episode-vue-apps',
                    'podlove_vue',
                    [
                        'rest_url' => esc_url_raw(rest_url()),
                        'nonce' => wp_create_nonce('wp_rest'),
                        'post_id' => get_the_ID(),
                        'episode_id' => $episode->id,
                        'osf_active' => is_plugin_active('shownotes/shownotes.php'),
                    ]
                );

                add_filter('podlove_data_js', function ($data) use ($episode) {
                    $data['episode'] = [
                        'duration' => $episode->duration,
                        'id' => $episode->id
                    ];

                    $data['post'] = [
                        'id' => get_the_ID()
                    ];

                    $assignments = \Podlove\Model\AssetAssignment::get_instance();

                    $data['assignments'] = [
                        'image' => $assignments->image,
                        'chapters' => $assignments->chapters,
                        'transcript' => $assignments->transcript
                    ];

                    return $data;
                });
            }

            wp_enqueue_script('podlove-episode-vue-apps');
        }

        wp_enqueue_script('podlove-vue-app-client');
    }

    if (\Podlove\is_podlove_settings_screen() || $is_episode_edit_screen) {
        wp_enqueue_style('podlove-admin', \Podlove\PLUGIN_URL.'/css/admin.css', [], $version);
        wp_enqueue_style('podlove-admin-font', \Podlove\PLUGIN_URL.'/css/admin-font.css', [], $version);

        // chosen.js scripts & styles
        wp_enqueue_style('podlove-admin-chosen', \Podlove\PLUGIN_URL.'/js/admin/chosen/chosen.min.css', [], $version);
        wp_enqueue_style(
            'podlove-admin-image-chosen',
            \Podlove\PLUGIN_URL.'/js/admin/chosen/chosenImage.css',
            [],
            $version
        );

        wp_enqueue_script('podlove_admin', \Podlove\PLUGIN_URL.'/js/dist/podlove-admin.js', [
            'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker', 'wp-i18n',
        ], $version);

        wp_set_script_translations('podlove_admin', 'podlove-podcasting-plugin-for-wordpress');

        wp_enqueue_style('jquery-ui-style', \Podlove\PLUGIN_URL.'/js/admin/jquery-ui/css/smoothness/jquery-ui.css');

        wp_localize_script(
            'podlove_admin',
            'podlove_admin_global',
            [
                'rest_url' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'nonce_ajax' => wp_create_nonce('podlove_ajax'),
                'post_id' => get_the_ID(),
            ]
        );
    }
});
