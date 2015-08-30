<?php

add_action( 'admin_print_styles', function () {

	$screen = get_current_screen();

	$is_podlove_settings_screen = stripos($screen->id, 'podlove') !== false;
	$is_episode_edit_screen = $screen->base == 'post' && $screen->post_type == 'podcast';

	if ($is_podlove_settings_screen || $is_episode_edit_screen) {
		wp_register_style( 'podlove-admin', \Podlove\PLUGIN_URL . '/css/admin.css', array(), \Podlove\get_plugin_header( 'Version' ) );
		wp_enqueue_style( 'podlove-admin' );

		wp_register_style( 'podlove-admin-chosen', \Podlove\PLUGIN_URL . '/js/admin/chosen/chosen.min.css', array(), \Podlove\get_plugin_header( 'Version' ) );
		wp_enqueue_style( 'podlove-admin-chosen' );

		wp_register_style( 'podlove-admin-image-chosen', \Podlove\PLUGIN_URL . '/js/admin/chosen/chosenImage.css', array(), \Podlove\get_plugin_header( 'Version' ) );
		wp_enqueue_style( 'podlove-admin-image-chosen' );

		wp_register_style( 'podlove-admin-font', \Podlove\PLUGIN_URL . '/css/admin-font.css', array(), \Podlove\get_plugin_header( 'Version' ) );
		wp_enqueue_style( 'podlove-admin-font' );

		wp_register_script( 'podlove-cryptojs', \Podlove\PLUGIN_URL . '/js/admin/cryptojs/md5.js' );
		wp_enqueue_script( 'podlove-cryptojs' );
	}

} );