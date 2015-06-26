<?php

add_filter('posts_results', 'podlove_remove_trash_posts_from_the_posts', 10, 2);

/**
 * Filters trashed, imported posts from our posts out
 */
function podlove_remove_trash_posts_from_the_posts($posts, $wp_query) {
	global $wp_the_query;

	// Apply filter not in the backend and only on the main query
	if ( $wp_query->is_admin && $wp_the_query == $wp_query )
		return $posts;

	// No post request
	if ( isset( $wp_query->query["preview"] ) || false === ( isset( $wp_query->query["name"] ) || isset( $wp_query->query["p"] ) ) )
		return $posts;

	// Only check if we found more than 2 posts
	if ( 2 > count( $posts ) )
		return $posts;

	// Remove trashed posts
	foreach ( $posts as $index => $post ) {
		if ( 'trash' == $post->post_status ) {
			unset( $posts[$index] );
		}
	}

	// Resets array keys
	$posts = array_values( $posts );
	
	return $posts;
}
