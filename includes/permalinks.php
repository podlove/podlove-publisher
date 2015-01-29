<?php
/**
 * Permalink magic
 */

if ( get_option( 'permalink_structure' ) != '' ) {
	add_action( 'after_setup_theme', 'podlove_add_podcast_rewrite_rules', 99 );
	add_action( 'permalink_structure_changed', 'podlove_add_podcast_rewrite_rules' );
	add_action( 'wp', 'podlove_no_verbose_page_rules' );		
	add_filter( 'post_type_link', 'podlove_generate_custom_post_link', 10, 4 );
	add_filter( 'post_rewrite_rules', 'podlove_add_podcast_episode_rules_to_post_rules' );

	if ( podlove_and_wordpress_permastructs_are_equal() ) {
		add_filter( 'request', 'podlove_podcast_permalink_proxy' );
	}
}

function podlove_and_wordpress_permastructs_are_equal() {

	if ( \Podlove\get_setting( 'website', 'use_post_permastruct' ) == 'on' )
		return true;

	return untrailingslashit( \Podlove\get_setting( 'website', 'custom_episode_slug' ) ) == untrailingslashit( str_replace( '%postname%', '%podcast%', get_option( 'permalink_structure' ) ) );
}

/**
 * Changes the permalink for a custom post type
 *
 * @uses $wp_rewrite
 */
function podlove_add_podcast_rewrite_rules() {
	global $wp_rewrite;
	
	// Get permalink structure
	$permastruct = \Podlove\get_setting( 'website', 'custom_episode_slug' );

	// Add rewrite tag
	$wp_rewrite->add_rewrite_tag( "%podcast%", '([^/]+)', "post_type=podcast&name=" );
	
	// Use same permastruct as post_type 'post'
	if ( podlove_and_wordpress_permastructs_are_equal() )
		$permastruct = str_replace( '%postname%', '%podcast%', get_option( 'permalink_structure' ) );

	// Enable generic rules for pages if permalink structure doesn't begin with a wildcard
	if ( "/%podcast%" == untrailingslashit( $permastruct ) ) {
		// Generate custom rewrite rules
		$wp_rewrite->matches = 'matches';
		$wp_rewrite->extra_rules = array_merge(
			$wp_rewrite->extra_rules,
			$wp_rewrite->generate_rewrite_rules( "%podcast%", EP_PERMALINK, true, true, false, true, true )
		);
		$wp_rewrite->matches = '';
		
		// Add for WP_Query
		$wp_rewrite->use_verbose_page_rules = true;
	}
	
	// Add archive pages
	if ( 'on' == \Podlove\get_setting( 'website', 'episode_archive' ) ) {
		$archive_slug = trim( \Podlove\get_setting( 'website', 'episode_archive_slug' ), '/' );

		$blog_prefix = \Podlove\get_blog_prefix();
		$blog_prefix = $blog_prefix ? trim( $blog_prefix, '/' ) . '/' : '';

		$wp_rewrite->add_rule( "{$blog_prefix}{$archive_slug}/?$", "index.php?post_type=podcast", 'top' );
		$wp_rewrite->add_rule( "{$blog_prefix}{$archive_slug}/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$", 'index.php?post_type=podcast&paged=$matches[1]', 'top' );
	}
}

/**
 * Add podcast episode rules to post rules
 * 
 * Add to post rewrite rules our rules for a podcast episode to respect correct
 * rule order. Needed to not interfere with other rules (like feeds).
 * 
 * @since 1.10.17
 * 
 * @param array $post_rewrite The rewrite rules for posts.
 * @return array An associate array of matches and queries.
 */
function podlove_add_podcast_episode_rules_to_post_rules( $post_rewrite ) {
	global $wp_rewrite;

	// Get permalink structure
	$permastruct = \Podlove\get_setting( 'website', 'custom_episode_slug' );

	// Use same permastruct as post_type 'post'
	if ( podlove_and_wordpress_permastructs_are_equal() )
		$permastruct = str_replace( '%postname%', '%podcast%', get_option( 'permalink_structure' ) );

	// Don't add rules here, if use the other method
	// @see \Podlove\add_podcast_rewrite_rules
	if ( "/%podcast%" == untrailingslashit( $permastruct ) )
		return $post_rewrite;
	
	// Generate rules for podcast episode and merge them with post rules
	$post_rewrite = array_merge( $wp_rewrite->generate_rewrite_rules( $permastruct, EP_PERMALINK, true, true, false, true, true ), $post_rewrite );

	return $post_rewrite;
}

/**
 * Filters the request query vars to search for posts with type 'post' and 'podcast'
 */
function podlove_podcast_permalink_proxy($query_vars) {
	global $wpdb;

	// Previews default to post type "post" which is unfortunate.
	// However, when there is a name, we can determine the post_type anyway.
	// I don't think this is 100% bulletproof but seems to work well enough.
	if ( isset( $query_vars["preview"] ) && ! isset( $query_vars["post_type"] ) && isset( $query_vars["name"] ) ) {
		$query_vars["post_type"] = $wpdb->get_var(
			$wpdb->prepare('SELECT post_type FROM ' . $wpdb->posts . ' WHERE post_name = %s', $query_vars['name'])
		);
	}

	// No post request
	if ( isset( $query_vars["preview"] ) || false === ( isset( $query_vars["name"] ) || isset( $query_vars["p"] ) ) )
		return $query_vars;
	
	if ( ! isset( $query_vars["post_type"] ) || $query_vars["post_type"] == "post" )
		$query_vars["post_type"] = array( "podcast", "post" );

	return $query_vars;
}

/**
 * Disable verbose page rules mode after startup
 *
 * @uses $wp_rewrite
 */
function podlove_no_verbose_page_rules() {
	global $wp_rewrite;
	$wp_rewrite->use_verbose_page_rules = false;
}

/**
 * Replace placeholders in permalinks with the correct values
 */
function podlove_generate_custom_post_link( $post_link, $id, $leavename = false, $sample = false ) {
	// Get post
	$post = get_post( $id );

	// Generate urls only for podcast episodes
	if ( 'podcast' != $post->post_type )
		return $post_link;

	// Draft or pending?
	$draft_or_pending = isset( $post->post_status ) && in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) );

	// Sample
	if ( $sample )
		$post->post_name = "%pagename%";
	
	// Get permastruct
	$permastruct = \Podlove\get_setting( 'website', 'custom_episode_slug' );

	if ( podlove_and_wordpress_permastructs_are_equal() )
		$permastruct = str_replace( '%postname%', '%podcast%', get_option( 'permalink_structure' ) );
	
	// Only post_name in URL
	if ( "/%podcast%" == untrailingslashit( $permastruct ) && ( !$draft_or_pending || $sample ) )
		return home_url( user_trailingslashit( $post->post_name ) );
	
	// Generate post link
	if ( !$draft_or_pending || $sample ) {
		$post_link = home_url( user_trailingslashit( $permastruct ) );
	}

	// Replace simple placeholders
	$unixtime = strtotime( $post->post_date );
	$post_link = str_replace( '%year%', date( 'Y', $unixtime ), $post_link );
	$post_link = str_replace( '%monthnum%', date( 'm', $unixtime ), $post_link );
	$post_link = str_replace( '%day%', date( 'd', $unixtime ), $post_link );
	$post_link = str_replace( '%hour%', date( 'H', $unixtime ), $post_link );
	$post_link = str_replace( '%minute%', date( 'i', $unixtime ), $post_link );
	$post_link = str_replace( '%second%', date( 's', $unixtime ), $post_link );
	$post_link = str_replace( '%post_id%', $post->ID, $post_link );
	$post_link = str_replace( '%podcast%', $post->post_name, $post_link );

	// category and author replacement copied from WordPress core
	if ( false !== strpos( $permastruct, '%category%' ) ) {
		$cats = get_the_category( $post->ID );

		if ( $cats ) {
			usort( $cats, '_usort_terms_by_ID' ); // order by ID
			
			$category_object = apply_filters( 'post_link_category', $cats[0], $cats, $post );
			$category_object = get_term( $category_object, 'category' );
			$category = $category_object->slug;
			
			if ( $parent = $category_object->parent ) {
				$category = get_category_parents( $parent, false, '/', true ) . $category;
			}
		}

		if ( empty( $category ) ) {
			$default_category = get_category( get_option( 'default_category' ) );
			$category = is_wp_error( $default_category ) ? '' : $default_category->slug;
		}

		$post_link = str_replace( '%category%', $category, $post_link );
	}

	if ( false !== strpos( $permastruct, '%author%' ) ) {
		$authordata = get_userdata($post->post_author);
		$post_link = str_replace( '%author%', $authordata->user_nicename, $post_link );
	}

	return $post_link;
}
