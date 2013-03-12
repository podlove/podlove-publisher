<?php
namespace Podlove\Feeds;
use \Podlove\Model;

function init() {
	add_feed_routes();
}

function add_feed_routes() {

	add_action( 'generate_rewrite_rules', function ( $wp_rewrite ) {
		
		if ( ! $feeds = Model\Feed::all() )
			return;

		foreach ( $feeds as $feed ) {
			$rule = "feed/" . $feed->slug;
			$new_rules = array( $rule => 'index.php?feed_slug=' . $feed->slug );
			$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		}
	} );

	add_filter( 'query_vars', function ( $qv ) {
		$qv[] = 'feed_slug';
		return $qv;
	} );
}

// set `is_feed()` correctly
add_action( 'parse_query', function ( $wp_query ) {
	if ( $feed = Model\Feed::find_one_by_slug( get_query_var( 'feed_slug' ) ) )
		$wp_query->is_feed = true;
} );

function override_feed_item_limit( $limits ) {
	global $wp_query;

	if ( ! is_feed() )
		return $limits;

	if ( ! $feed = \Podlove\Model\Feed::find_one_by_slug( get_query_var( 'feed_slug' ) ) )
		return $limits;

	$custom_limit = (int) $feed->limit_items;

	if ( $custom_limit > 0 ) {
		return "LIMIT $custom_limit";	
	} elseif ( $custom_limit == 0 ) {
		return $limits; // WordPress default
	} else {
		return ''; // no limit
	}
}
add_filter( 'post_limits', '\Podlove\Feeds\override_feed_item_limit', 20, 1 );

/**
 * Make sure that PodPress doesn't vomit anything into our precious feeds
 * in case it is still active.
 */
function remove_podPress_hooks() {
	remove_filter( 'option_blogname', 'podPress_feedblogname' );
	remove_filter( 'option_blogdescription', 'podPress_feedblogdescription' );
	remove_filter( 'option_rss_language', 'podPress_feedblogrsslanguage' );
	remove_filter( 'option_rss_image', 'podPress_feedblogrssimage' );
	remove_action( 'rss2_ns', 'podPress_rss2_ns' );
	remove_action( 'rss2_head', 'podPress_rss2_head' );
	remove_filter( 'rss_enclosure', 'podPress_dont_print_nonpodpress_enclosures' );
	remove_action( 'rss2_item', 'podPress_rss2_item' );
	remove_action( 'atom_head', 'podPress_atom_head' );
	remove_filter( 'atom_enclosure', 'podPress_dont_print_nonpodpress_enclosures' );
	remove_action( 'atom_entry', 'podPress_atom_entry' );
}

function remove_powerPress_hooks() {
	remove_action( 'rss2_ns', 'powerpress_rss2_ns' );
	remove_action( 'rss2_head', 'powerpress_rss2_head' );
	remove_action( 'rss2_item', 'powerpress_rss2_item' );
}

// Hooks:
// parse_query => query vars available
// wp => query_posts done
add_action( 'wp', function () {
	global $wp_query;

	if ( ! $feed = Model\Feed::find_one_by_slug( get_query_var( 'feed_slug' ) ) )
		return;

	$is_feedburner_bot = preg_match( "/feedburner|feedsqueezer/i", $_SERVER['HTTP_USER_AGENT'] );
	$is_manual_redirect = ! isset( $_REQUEST['redirect'] ) || $_REQUEST['redirect'] != "no";

	if ( strlen( $feed->redirect_url ) > 0 && $is_manual_redirect && ! $is_feedburner_bot && $feed->redirect_http_status > 0 ) {
		header( sprintf( "Location: %s", $feed->redirect_url ), TRUE, $feed->redirect_http_status );
		exit;
	} else {

		remove_podPress_hooks();
		remove_powerPress_hooks();

		if ( $feed->format === "rss" ) {
			new	\Podlove\Feeds\RSS( $feed->slug );
		} else {
			new	\Podlove\Feeds\Atom( $feed->slug );
		}
	}
	
} );
