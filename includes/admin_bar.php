<?php

// add podlove to admin bar
add_action( 'admin_bar_menu', function ( $wp_admin_bar ) {
	$wp_admin_bar->add_node( array(
		'id'     => 'podlove-settings',
		'parent' => 'site-name',
		'title'  => 'Podlove',
		'href'   => admin_url( 'admin.php?page=podlove_settings_handle' )
	) );
}, 50 );