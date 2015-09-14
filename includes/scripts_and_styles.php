<?php

// admin styles & scripts
add_action( 'admin_print_styles', function () {

	$screen = get_current_screen();

	$is_podlove_settings_screen = stripos($screen->id, 'podlove') !== false;
	$is_episode_edit_screen = in_array($screen->base, ['edit', 'post']) && $screen->post_type == 'podcast';

	if ($is_podlove_settings_screen || $is_episode_edit_screen) {

		$version = \Podlove\get_plugin_header('Version');

		wp_enqueue_style('podlove-admin',      \Podlove\PLUGIN_URL . '/css/admin.css', [], $version);
		wp_enqueue_style('podlove-admin-font', \Podlove\PLUGIN_URL . '/css/admin-font.css', [], $version);

		// chosen.js scripts & styles
		wp_enqueue_style('podlove-admin-chosen',        \Podlove\PLUGIN_URL . '/js/admin/chosen/chosen.min.css', [], $version);
		wp_enqueue_style('podlove-admin-image-chosen',  \Podlove\PLUGIN_URL . '/js/admin/chosen/chosenImage.css', [], $version);
		wp_enqueue_script('podlove_admin_chosen',       \Podlove\PLUGIN_URL . '/js/admin/chosen/chosen.jquery.min.js', ['jquery'], $version);
		wp_enqueue_script('podlove_admin_chosen_image', \Podlove\PLUGIN_URL . '/js/admin/chosen/chosenImage.jquery.js', ['jquery'], $version);

		// other scripts
		wp_enqueue_script('podlove-cryptojs',                         \Podlove\PLUGIN_URL . '/js/admin/cryptojs/md5.js');
		wp_enqueue_script('podlove_admin_episode',                    \Podlove\PLUGIN_URL . '/js/admin/episode.js', ['jquery'], $version);
		wp_enqueue_script('podlove_admin_dashboard_asset_validation', \Podlove\PLUGIN_URL . '/js/admin/dashboard_asset_validation.js', ['jquery'], $version);
		wp_enqueue_script('podlove_admin_dashboard_feed_validation',  \Podlove\PLUGIN_URL . '/js/admin/dashboard_feed_validation.js', 	['jquery'], $version);
		wp_enqueue_script('podlove_admin_episode_asset_settings',     \Podlove\PLUGIN_URL . '/js/admin/episode_asset_settings.js', ['jquery', 'jquery-ui-sortable'], $version);
		wp_enqueue_script('podlove_admin_episode_feed_settings',      \Podlove\PLUGIN_URL . '/js/admin/feed_settings.js', ['jquery'], $version);
		wp_enqueue_script('podlove_admin_autogrow',                   \Podlove\PLUGIN_URL . '/js/admin/jquery.autogrow.js', ['jquery'], $version);
		wp_enqueue_script('podlove_admin_count_characters',           \Podlove\PLUGIN_URL . '/js/admin/jquery.count_characters.js', ['jquery'], $version);
		wp_enqueue_script('podlove_admin_license',                    \Podlove\PLUGIN_URL . '/js/admin/license.js', ['jquery'], $version);
		wp_enqueue_script('podlove_admin_protected_feed',             \Podlove\PLUGIN_URL . '/js/admin/protected_feed.js', ['jquery'], $version);
		wp_enqueue_script('podlove_admin_data_table',                 \Podlove\PLUGIN_URL . '/js/admin/podlove_data_table.js', ['jquery'], $version);
		wp_enqueue_script('podlove_admin_media',                      \Podlove\PLUGIN_URL . '/js/admin/media.js', ['jquery'], $version);
		wp_enqueue_script('podlove_admin',                            \Podlove\PLUGIN_URL . '/js/admin.js', ['jquery', 'jquery-ui-datepicker'], $version);

		wp_enqueue_style('jquery-ui-style', \Podlove\PLUGIN_URL . '/js/admin/jquery-ui/css/smoothness/jquery-ui.css');
	}

} );

// frontend styles & scripts
add_action( 'wp_enqueue_scripts', function() {

	$version = \Podlove\get_plugin_header( 'Version' );

	wp_enqueue_script('podlove_frontend', \Podlove\PLUGIN_URL . '/js/frontend.js',	['jquery'],	$version);

} );
