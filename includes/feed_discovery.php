<?php

use \Podlove\Cache\TemplateCache;
use \Podlove\Model;
use \Podlove\Feeds;

/**
 * Adds feed discover links to WordPress head.
 */
function podlove_add_feed_discoverability() {

	if ( is_admin() )
		return;

	if ( ! function_exists( '\Podlove\Feeds\prepare_for_feed' ) )
		require_once \Podlove\PLUGIN_DIR . 'lib/feeds/base.php';

	// we need separate caches for http and https
	$cache_key = 'feed_discoverability_' . (int) is_ssl();
	echo TemplateCache::get_instance()->cache_for($cache_key, function() {

		$feeds = Model\Podcast::get()->feeds();

		$html = '';
		foreach ( $feeds as $feed ) {
			if ( $feed->discoverable )
				$html .= '<link rel="alternate" type="' . $feed->get_content_type() . '" title="' . Feeds\prepare_for_feed( $feed->title_for_discovery() ) . '" href="' . $feed->get_subscribe_url() . "\" />\n";			
		}
		return $html;
	});
}

add_action( 'init', function () {
	
	// priority 2 so they are placed below the WordPress default discovery links
	add_action( 'wp_head', 'podlove_add_feed_discoverability', 2 );

	// hide WordPress default link discovery
	if ( \Podlove\get_setting( 'website', 'hide_wp_feed_discovery' ) === 'on' ) {
		remove_action( 'wp_head', 'feed_links',       2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}
});
