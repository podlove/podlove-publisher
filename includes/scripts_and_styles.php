<?php

// admin styles & scripts
add_action( 'admin_print_styles', function () {

	$screen = get_current_screen();

	$is_podlove_settings_screen = stripos($screen->id, 'podlove') !== false;
	$is_episode_edit_screen = in_array($screen->base, ['edit', 'post']) && $screen->post_type == 'podcast';

	$version = \Podlove\get_plugin_header('Version');

	// vue job dashboard
	if ($is_episode_edit_screen || $screen->base === 'podlove_page_podlove_tools_settings_handle') {
		wp_enqueue_script('podlove-episode-vue-apps', \Podlove\PLUGIN_URL . '/js/dist/app.js', ['underscore', 'jquery'], $version, true );
	}

	if ($is_podlove_settings_screen || $is_episode_edit_screen) {

		wp_enqueue_style('podlove-admin',      \Podlove\PLUGIN_URL . '/css/admin.css', [], $version);
		wp_enqueue_style('podlove-admin-font', \Podlove\PLUGIN_URL . '/css/admin-font.css', [], $version);

		// chosen.js scripts & styles
		wp_enqueue_style('podlove-admin-chosen',        \Podlove\PLUGIN_URL . '/js/admin/chosen/chosen.min.css', [], $version);
		wp_enqueue_style('podlove-admin-image-chosen',  \Podlove\PLUGIN_URL . '/js/admin/chosen/chosenImage.css', [], $version);

		wp_enqueue_script('podlove_admin', \Podlove\PLUGIN_URL . '/js/dist/podlove-admin.js', [
			'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker'
		], $version);

		wp_enqueue_style('jquery-ui-style', \Podlove\PLUGIN_URL . '/js/admin/jquery-ui/css/smoothness/jquery-ui.css');
	}

} );

// frontend styles & scripts
add_action( 'wp_enqueue_scripts', function() {

	$version = \Podlove\get_plugin_header( 'Version' );

	wp_enqueue_script('podlove_frontend', \Podlove\PLUGIN_URL . '/js/frontend.js',	['jquery'],	$version);

} );
