<?php
namespace Podlove\Feeds;

require_once \Podlove\PLUGIN_DIR  . '/lib/feeds/base.php';

class RSS {
	
	public function __construct( $show_slug, $feed_slug ) {
		// @fixme either slugs are unique or we need to check for id or something
		$show           = \Podlove\Model\Show::find_one_by_slug( $show_slug );
		$feed           = \Podlove\Model\Feed::find_one_by_slug( $feed_slug );
		$media_location = $feed->media_location();
		$format         = $media_location->media_format();

		add_filter( 'podlove_feed_enclosure', function ( $enclosure, $enclosure_url, $enclosure_file_size, $mime_type ) {
			return sprintf( '<enclosure url="%s" length="%s" type="%s" />', $enclosure_url, $enclosure_file_size, $mime_type );
		}, 10, 4 );

		mute_feed_title();
		override_feed_title( $feed );
		override_feed_language( $feed );
		override_feed_head( 'rss2_head', $show, $feed, $format );
		override_feed_entry( 'rss2_item', $show, $feed, $format );

		$this->do_feed();
	}
	
	function do_feed() {
		global $wp_query;
		
		$args = array(
			'post_type'=> 'podcast'
		);
		query_posts( $args );
		
		if ( $wp_query->is_comment_feed )
			load_template( ABSPATH . WPINC . '/feed-rss2-comments.php');
		else
			load_template( \Podlove\PLUGIN_DIR . 'templates/feed-rss2.php' );
			
		exit;
		
	}
	
}