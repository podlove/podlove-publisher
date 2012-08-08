<?php
namespace Podlove\Feeds;

function init() {
	add_feed_routes();
}

function add_feed_routes() {

	// The following defines a rule that maps URLs like /geostate/oregon to a URL request like ?geostate=oregon
	add_action( 'generate_rewrite_rules', function ( $wp_rewrite ) {
		$new_rules = array( 
			'feed/(.+)/(.+)' => 'index.php?show_slug=' . $wp_rewrite->preg_index( 1 ) . '&feed_slug=' . $wp_rewrite->preg_index( 2 )
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
	
	$show_slug = get_query_var( 'show_slug' );
	$feed_slug = get_query_var( 'feed_slug' );
	
	if ( ! $show_slug || ! $feed_slug )
		return;

	$feed = \Podlove\Model\Feed::find_by_show_slug_and_feed_slug( $show_slug, $feed_slug );

	if ( ! $feed )
		return;

	$is_feedburner_bot = preg_match( "/feedburner|feedsqueezer/i", $_SERVER['HTTP_USER_AGENT'] );
	$is_manual_redirect = ! isset( $_REQUEST['redirect'] ) || $_REQUEST['redirect'] != "no";

	if ( strlen( $feed->redirect_url ) > 0 && $is_manual_redirect && ! $is_feedburner_bot ) {
		header( sprintf( "Location: %s", $feed->redirect_url ), TRUE, 302 );
		exit;
	} else {

		// make sure is_feed() returns true
		add_filter( 'the_content', function ( $content ) {
			global $wp_query;
			$wp_query->is_feed = true;
			return $content;
		} );

		if ( $feed->format === "rss" ) {
			new	\Podlove\Feeds\RSS( $show_slug, $feed_slug );
		} else {
			new	\Podlove\Feeds\Atom( $show_slug, $feed_slug );
		}
	}
	
} );
