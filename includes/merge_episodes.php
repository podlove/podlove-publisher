<?php
/**
 * Handle "Merge Episodes" setting
 */

/**
 * Checking "merge_episodes" allows to see episodes on the front page.
 */
add_action( 'pre_get_posts', function ( $wp_query ) {

	if ( \Podlove\get_setting( 'website', 'merge_episodes' ) !== 'on' )
		return;

	if ( is_home() && $wp_query->is_main_query() && ! isset( $wp_query->query_vars["post_type"] ) ) {
		$wp_query->set(
			'post_type',
			array_merge( array( 'post', 'podcast' ), (array) $wp_query->get( 'post_type' ) )
		);
	}
} );

/**
 * Checking "merge_episodes" also includes episodes in main feed
 */
add_filter( 'request', function($query_var) {

	if ( !isset( $query_var['feed'] ) ) 
		return $query_var;
	
	if ( \Podlove\get_setting( 'website', 'merge_episodes' ) !== 'on' )
		return $query_var;
	
	$extend = array(
		'post' => 'post',
		'podcast' => 'podcast'
	);

	if ( empty( $query_var['post_type'] ) || ! is_array( $query_var['post_type'] ) ) {
		$query_var['post_type'] = $extend;
	} else {
		$query_var['post_type'] = array_merge( $query_var['post_type'], $extend );
	}
	
	return $query_var;
} );