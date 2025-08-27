<?php

function add_type_attribute($tag, $handle, $src)
{
    // if not your script, do nothing and return original $tag
    if ('podlove-vue-app-client' !== $handle) {
        return $tag;
    }

    // change the script tag by adding type="module" and return it.
    return '<script crossorigin type="module" src="'.esc_url($src).'"></script>';
}

// admin styles & scripts
add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();

    $is_episode_edit_screen = \Podlove\is_episode_edit_screen();

    $version = \Podlove\get_plugin_header('Version');

    $vue_screens = [
        'podlove_page_podlove_slackshownotes_settings',
        'podlove_page_podlove_tools_settings_handle',
        'podlove_page_podlove_analytics',
        'podlove-setup-wizard',
        'podlove_page_publisher_plus_settings',
    ];

    // vue job dashboard
    if ($is_episode_edit_screen || in_array($screen->base, $vue_screens)) {
        wp_register_script('podlove-episode-vue-apps', \Podlove\PLUGIN_URL.'/js/dist/app.js', ['underscore', 'jquery'], $version, true);
        wp_register_script('podlove-vue-app-client', \Podlove\PLUGIN_URL.'/client/dist/client.js', ['wp-i18n'], $version, false);
        add_filter('script_loader_tag', 'add_type_attribute', 10, 3);
        wp_enqueue_style('podlove-vue-app-client-css', \Podlove\PLUGIN_URL.'/client/dist/style.css', [], $version);

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

                $data['api'] = [
                    'base' => esc_url_raw(rest_url('podlove')),
                    'nonce' => wp_create_nonce('wp_rest'),
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

        wp_set_script_translations('podlove-vue-app-client', 'podlove-podcasting-plugin-for-wordpress');

        wp_enqueue_script('podlove-episode-vue-apps');
        wp_enqueue_script('podlove-vue-app-client');
    }

    if (\Podlove\is_podlove_settings_screen() || $is_episode_edit_screen) {
        wp_enqueue_style('podlove-admin', \Podlove\PLUGIN_URL.'/css/admin.css', [], $version);
        wp_enqueue_style('podlove-admin-font', \Podlove\PLUGIN_URL.'/css/admin-font.css', [], $version);

        // chosen.js scripts & styles
        wp_enqueue_style('podlove-admin-chosen', \Podlove\PLUGIN_URL.'/js/admin/chosen/chosen.min.css', [], $version);
        wp_enqueue_style('podlove-admin-image-chosen', \Podlove\PLUGIN_URL.'/js/admin/chosen/chosenImage.css', [], $version);

        wp_enqueue_script('podlove_admin', \Podlove\PLUGIN_URL.'/js/dist/podlove-admin.js', [
            'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker',
        ], $version);

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
