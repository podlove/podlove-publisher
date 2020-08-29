<?php
/**
 * register frontend styles.
 */
add_action('init', function () {
    if (is_admin()) {
        return;
    }

    wp_register_style(
        'podlove-frontend-css',
        \Podlove\PLUGIN_URL.'/css/frontend.css',
        [],
        '1.0'
    );
    wp_enqueue_style('podlove-frontend-css');

    wp_register_style('podlove-admin-font', \Podlove\PLUGIN_URL.'/css/admin-font.css', [], \Podlove\get_plugin_header('Version'));
    wp_enqueue_style('podlove-admin-font');
});
