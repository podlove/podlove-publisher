<?php
namespace Podlove\Feeds;

require_once \Podlove\PLUGIN_DIR  . '/lib/feeds/base.php';

class Atom {
	
	public function __construct( $show_slug, $feed_slug ) {
		
		add_action( 'atom_ns', function () {
			echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"';
		} );
		
		// @fixme either slugs are unique or we need to check for id or something
		$show           = \Podlove\Model\Show::find_one_by_slug( $show_slug );
		$feed           = \Podlove\Model\Feed::find_one_by_slug( $feed_slug );
		$media_location = $feed->media_location();
		$format         = $media_location->media_format();

		add_filter( 'podlove_feed_enclosure', function ( $enclosure, $enclosure_url, $enclosure_file_size, $mime_type ) {
			return sprintf( '<link rel="enclosure" href="%s" length="%s" type="%s"/>', $enclosure_url, $enclosure_file_size, $mime_type );
		}, 10, 4 );

		mute_feed_title();
		override_feed_title( $feed );
		override_feed_language( $feed );
		override_feed_head( 'atom_head', $show, $feed, $format );
		override_feed_entry( 'atom_entry', $show, $feed, $format );

		$this->do_feed();
	}
	
	function do_feed() {
		global $wp_query;
		
		$args = array(
			'post_type'=> 'podcast'
		);
		query_posts( $args );
		
		if ( $wp_query->is_comment_feed )
			load_template( ABSPATH . WPINC . '/feed-atom-comments.php');
		else
			load_template( \Podlove\PLUGIN_DIR . 'templates/feed-atom.php' );
			
		exit;
	}
	
}