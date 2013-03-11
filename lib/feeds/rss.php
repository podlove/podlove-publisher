<?php
namespace Podlove\Feeds;
use \Podlove\Model;

require_once \Podlove\PLUGIN_DIR  . '/lib/feeds/base.php';

class RSS {
	
	public function __construct( $feed_slug ) {
		
		add_action( 'rss2_ns', function () {
			echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" ';
			echo 'xmlns:psc="http://podlove.org/simple-chapters" ';
		} );

		$podcast        = Model\Podcast::get_instance();
		$feed           = Model\Feed::find_one_by_slug( $feed_slug );
		$episode_asset  = $feed->episode_asset();
		$file_type      = $episode_asset->file_type();

		add_filter( 'podlove_feed_enclosure', function ( $enclosure, $enclosure_url, $enclosure_file_size, $mime_type ) {

			if ( $enclosure_file_size < 0 )
				$enclosure_file_size = 0;

			return sprintf( '<enclosure url="%s" length="%s" type="%s" />', $enclosure_url, $enclosure_file_size, $mime_type );
		}, 10, 4 );

		override_feed_title( $feed );
		override_feed_language( $feed );
		override_feed_head( 'rss2_head', $podcast, $feed, $file_type );
		override_feed_entry( 'rss2_item', $podcast, $feed, $file_type );

		add_action( 'rss2_item', function () {
			if ( apply_filters( 'podlove_feed_show_summary', true ) ) {
				echo "<description><![CDATA[";
				\Podlove\Feeds\the_description();
				echo "]]></description>";
			}
		} );

		add_action( 'rss2_head', function () use ( $podcast ) {
			if ( $podcast->language )
				echo "\t" . '<language>' . $podcast->language . '</language>';
		}, 100 );

		$this->do_feed( $feed );
	}
	
	function do_feed( $feed ) {

		global $wp_query;
		
		$args = array(
			'post_type'      => 'podcast',
			'post__in'       => $feed->post_ids(),
			'posts_per_page' => $feed->limit_items == 0 ? get_option( 'posts_per_rss' ) : $feed->limit_items
		);
		$args = array_merge( $wp_query->query_vars, $args );
		query_posts( $args );
		
		if ( $wp_query->is_comment_feed )
			load_template( ABSPATH . WPINC . '/feed-rss2-comments.php');
		else
			load_template( \Podlove\PLUGIN_DIR . 'templates/feed-rss2.php' );
			
		exit;
		
	}
	
}

