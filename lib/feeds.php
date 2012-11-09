<?php
namespace Podlove\Feeds;

function init() {
	add_feed_routes();
}

function add_feed_routes() {

	add_action( 'generate_rewrite_rules', function ( $wp_rewrite ) {
		$new_rules = array( 
			'feed/(.+)' => 'index.php?feed_slug=' . $wp_rewrite->preg_index( 1 )
		);
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	} );

	add_filter( 'query_vars', function ( $qv ) {
		$qv[] = 'show_slug';
		$qv[] = 'feed_slug';
		return $qv;
	} );

}

// Hooks:
// parse_query => query vars available
// wp => query_posts done
add_action( 'wp', function () {
	global $wp_query;
	
	if ( ! $feed_slug = get_query_var( 'feed_slug' ) )
		return;

	if ( ! $feed = \Podlove\Model\Feed::find_one_by_slug( $feed_slug ) )
		return;

	if ( ! $feed->redirect_http_status )
		return;

	$is_feedburner_bot = preg_match( "/feedburner|feedsqueezer/i", $_SERVER['HTTP_USER_AGENT'] );
	$is_manual_redirect = ! isset( $_REQUEST['redirect'] ) || $_REQUEST['redirect'] != "no";

	if ( strlen( $feed->redirect_url ) > 0 && $is_manual_redirect && ! $is_feedburner_bot ) {
		header( sprintf( "Location: %s", $feed->redirect_url ), TRUE, $feed->redirect_http_status );
		exit;
	} else {

		// make sure is_feed() returns true
		add_filter( 'the_content', function ( $content ) {
			global $wp_query;
			$wp_query->is_feed = true;
			return $content;
		} );

		if ( $feed->format === "rss" ) {
			new	\Podlove\Feeds\RSS( $feed_slug );
		} else {
			new	\Podlove\Feeds\Atom( $feed_slug );
		}
	}
	
} );
