<?php
/**
 * Saves a custom guid for podlove episodes.
 *
 * Therefore we have to convince WordPress it's not a URL. Hence removing all
 * associated filters. Then we can save our own guid. For easy recognition 
 * prefixed with "plv-".
 */
namespace Podlove;

remove_filter( 'pre_post_guid', 'wp_strip_all_tags' );
remove_filter( 'pre_post_guid', 'esc_url_raw' );
remove_filter( 'pre_post_guid', 'wp_filter_kses' );
remove_filter( 'post_guid',     'wp_strip_all_tags' );
remove_filter( 'post_guid',     'esc_url' );
remove_filter( 'post_guid',     'wp_kses_data' );

// todo: when we import a feed from somewhere, the guid must stay the same
add_filter( 'post_guid', function ( $guid ) {
	if ( false === strpos( $guid, 'plv-' ) )
		$guid = uniqid( 'plv-', true ); 

	return $guid; 
} );