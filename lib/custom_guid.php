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

add_action( 'save_post', '\Podlove\save_custom_guid' );

function save_custom_guid( $post_id ) {
	global $wpdb;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;

	if ( empty( $_POST[ 'podlove_noncename' ] ) || ! wp_verify_nonce( $_POST[ 'podlove_noncename' ], \Podlove\PLUGIN_FILE ) )
		return;

	// Check permissions
	if ( 'podcast' == $_POST['post_type'] ) {
	  if ( ! current_user_can( 'edit_post', $post_id ) )
		return;
	} else {
		return;
	}

	// set custom guid
	$current_guid = get_post_field( 'guid', $post_id );
	if ( false === strpos( $current_guid, 'plv-' ) ) {
		$guid = uniqid( 'plv-', true ); 
		$wpdb->update( $wpdb->posts, array( 'guid' => $guid ), array( 'ID' => $post_id ) );
	}
}
