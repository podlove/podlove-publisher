<?php

/**
 * Get URL for toolbar icon.
 * 
 * @return string Network dashboard URL if it's multisit, else local dashboard.
 */
function podlove_get_toolbar_url() {
	if (is_multisite()) {
		return network_admin_url('admin.php?page=podlove_network_settings_handle');
	} else {
		return admin_url('admin.php?page=podlove_settings_handle');
	}
}

function podlove_create_admin_toolbar($wp_admin_bar) {
	
	// add toplevel toolbar entry
	$wp_admin_bar->add_node([
		'id'     => 'podlove_toolbar',
		'title'  => 'Podlove',
		'href'   => podlove_get_toolbar_url(),
		'meta'   => ['class' => 'podlove-toolbar-opener']
	]);

	// add link to episodes
	$wp_admin_bar->add_node([
		'id'     => 'podlove_toolbar_episodes',
		'title'  => __( 'Episodes', 'podlove' ),
		'parent' => 'podlove_toolbar',
		'href'   => get_admin_url('edit.php?post_type=podcast'),
		'meta'   => ['class' => 'podlove-toolbar-without-icon']
	]);

}

add_action('admin_bar_menu', 'podlove_create_admin_toolbar', 100);
