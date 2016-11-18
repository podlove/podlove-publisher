<?php
namespace Podlove\Feeds;
use \Podlove\Model;

function handle_feed_proxy_redirects() {

	if (!$feed_slug = get_query_var( 'feed' ))
		return;

	$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
	$is_debug_view = false;

	if (\Podlove\get_setting('website', 'feeds_skip_redirect') == 'on' && filter_input(INPUT_GET, 'redirect') == 'no') {
		$should_redirect = false;
		$is_debug_view = true;
	} elseif (preg_match("/feedburner|feedsqueezer|feedvalidator|feedpress/i", filter_input(INPUT_SERVER, 'HTTP_USER_AGENT'))) {
		$should_redirect = false;
	} else {
		$should_redirect = true;
	}

	$is_feed_page = $paged > 1;

	if (!$feed = Model\Feed::find_one_by_slug($feed_slug))
		return;

	/**
	 * Before we redirect to a proxy or deliver the feed, ensure that the canonical
	 * feed URL was accessed.
	 */
	if (!$is_debug_view && get_option('permalink_structure') != '') {

		$feed_url = $feed->get_subscribe_url();
		$request_url = "http" . (isset($_SERVER['HTTPS']) ? 's' : '') . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$url = parse_url($request_url);

		if (
			!\Podlove\PHP\ends_with($url['path'], '/') && \Podlove\PHP\ends_with($feed_url, '/')
			||
			\Podlove\PHP\ends_with($url['path'], '/') && !\Podlove\PHP\ends_with($feed_url, '/')
		) {

			if ($is_feed_page) {
				$feed_url = add_query_arg(['paged' => $paged], $feed_url);
			}

			wp_redirect($feed_url, 301);
			exit;
		}
	}

	// most HTTP/1.0 client's don't understand 307, so we fall back to 302
	$http_status_code = $_SERVER['SERVER_PROTOCOL'] == "HTTP/1.0" ? 302 : $feed->redirect_http_status;

	if ( ! $is_feed_page && strlen( $feed->redirect_url ) > 0 && $should_redirect && $http_status_code > 0 ) {
		header( sprintf( "Location: %s", $feed->redirect_url ), TRUE, $http_status_code );
		exit;
	} else { // don't redirect; prepare feed
		status_header(200);
		RSS::prepare_feed( $feed->slug );
	}

}

# Prio 11 so it hooks *after* the domain mapping plugin.
# This is important when one moves a domain. That way the domain gets
# remapped/redirected correctly by the domain mapper before being redirected by us.
add_action( 'template_redirect', '\Podlove\Feeds\handle_feed_proxy_redirects', 11 );

function generate_podcast_feed() {	
	remove_podPress_hooks();
	remove_powerPress_hooks();
	RSS::render();
}

add_action( 'init', function() {

	foreach ( Model\Feed::all() as $feed ) {
		if ($feed->slug)
			add_feed( $feed->slug,  "\Podlove\Feeds\generate_podcast_feed" );
	}

	// changing feed settings may affect permalinks, so we need to flush
	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'podlove_feeds_settings_handle' ) {
		set_transient( 'podlove_needs_to_flush_rewrite_rules', true );
	}

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

function feed_authentication() {
	header( 'WWW-Authenticate: Basic realm="This feed is protected. Please login."' );
	header( 'HTTP/1.1 401 Unauthorized' );
	exit;
}

function check_for_and_do_compression($content_type = 'application/rss+xml')
{
	// ensure content type headers are set
	if (!headers_sent())
		header('Content-type: ' . $content_type);
	
	if (!apply_filters('podlove_enable_gzip_for_feeds', true))
		return false;
	
	// gzip requires zlib extension
	if (!extension_loaded('zlib'))
		return false;

	// if zlib output compression is already active, don't gzip
	// (both cannot be active at the same time)
	$ob_status = ob_get_status();
	if (isset($ob_status['name']) && $ob_status['name'] == 'zlib output compression') {
		return false;
	}

	// don't gzip if client doesn't accept it
	if ( isset($_SERVER['HTTP_ACCEPT_ENCODING']) && stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) === FALSE)
		return false;

	// don't gzip if _any_ output buffering is active
	// this can be 1 if "output_buffering" is not set to off / 0
	// but better safe than sorry (otherwise there's trouble with caching plugins)
	if (ob_get_level() > 0) {
		return false;
	}

	// don't gzip if wprocket is active
	if (in_array('do_rocket_callback', ob_list_handlers()))
		return false;

	// don't gzip if gzipping is already active
	if (in_array('ob_gzhandler', ob_list_handlers()))
		return false;

	// don't try to use ob_gzhandler on hhvm, it's not supported
	// (see https://github.com/facebook/hhvm/issues/1854)
	if (defined('HHVM_VERSION'))
		return false;

	// start gzipping
	ob_start("ob_gzhandler");
}

add_action('pre_get_posts', function ( ) {
	global $wp_query;	

	if (!is_feed())
		return;

	$feedname = get_query_var('feed');
	$feed = \Podlove\Model\Feed::find_one_by_property('slug', $feedname);
	
	if ( isset($feed) && $feed->protected == 1 ) {
		if ( !isset( $_SERVER['PHP_AUTH_USER'] ) || !isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			feed_authentication();
		} else {
			switch ($feed->protection_type) {
				case '0' :
					// A local User/PW combination is set
					if ( $_SERVER['PHP_AUTH_USER'] == $feed->protection_user && $_SERVER['PHP_AUTH_PW'] == $feed->protection_password) {
						// let the script continue
						check_for_and_do_compression();
					} else {
						feed_authentication();
					}
				break;
				case '1' :
					// The WordPress User db is used for authentification
					if ( !username_exists($_SERVER['PHP_AUTH_USER'] ) ) {
						feed_authentication();
					} else {
						$userinfo = get_user_by( 'login', $_SERVER['PHP_AUTH_USER'] );
						if ( wp_check_password( $_SERVER['PHP_AUTH_PW'], $userinfo->data->user_pass, $userinfo->ID ) ) {
							// let the script continue
							check_for_and_do_compression();
						} else {
							feed_authentication();
						}
					}
				break;
				case '2':
					// A custom method is used for authentication
					if ( !isset($_SERVER['PHP_AUTH_USER'] ) ) {
						feed_authentication();
					} else {
						// Call custom login filters (most likely provided by other plugins)
						if ( apply_filters('podlove_feed_authenticate', true, $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) ) {
							// let the script continue
							check_for_and_do_compression();
						} else {
							feed_authentication();
						}
					}
				break;
				default :
					exit; // If the feed is protected and no auth method is selected exit the script
				break;
			}
		}
	} else {
		// compress unprotected feeds
		check_for_and_do_compression();
	}
});

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
