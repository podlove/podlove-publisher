<?php
namespace Podlove\Feeds;
use \Podlove\Model;

require_once \Podlove\PLUGIN_DIR  . '/lib/feeds/base.php';

class Atom {
	
	public function __construct( $feed_slug ) {
		
		add_action( 'atom_ns', function () {
			echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"';
		} );
		
		add_filter( 'feed_link', function ( $output, $feed ) use ( $feed_slug ) {
			return get_bloginfo( 'url' ) . '/feed/' . $feed_slug . '/';
		}, 10, 2 );
		
		
		$podcast        = Model\Podcast::get_instance();
		$feed           = Model\Feed::find_one_by_slug( $feed_slug );
		$media_location = $feed->media_location();
		$format         = $media_location->media_format();

		add_filter( 'podlove_feed_enclosure', function ( $enclosure, $enclosure_url, $enclosure_file_size, $mime_type ) {
			return sprintf( '<link rel="enclosure" href="%s" length="%s" type="%s"/>', $enclosure_url, $enclosure_file_size, $mime_type );
		}, 10, 4 );

		mute_feed_title();
		override_feed_title( $feed );
		override_feed_language( $feed );
		override_feed_head( 'atom_head', $podcast, $feed, $format );
		override_feed_entry( 'atom_entry', $podcast, $feed, $format );

		add_action( 'atom_head', function () use ( $podcast, $feed, $format ) {
			?>
			<link rel="self" type="application/atom+xml" title="<?php echo $feed->title_for_discovery(); ?>" href="<?php echo $feed->get_subscribe_url() ?>" />
			<?php
			$feeds = Model\Feed::all();
			foreach ( $feeds as $other_feed ) {
				if ( $other_feed->id !== $feed->id ):
					?>
					<link rel="alternate" type="application/atom+xml" title="<?php echo $other_feed->title_for_discovery(); ?>" href="<?php echo $other_feed->get_subscribe_url() ?>" />
					<?php
				endif;
			}
		}, 9 );

		$this->do_feed( $feed );
	}
	
	function do_feed( $feed ) {

		global $wp_query;

		$args = array(
			'post_type' => 'podcast',
			'post__in'   => $feed->post_ids()
		);
		query_posts( $args );
		
		if ( $wp_query->is_comment_feed )
			load_template( ABSPATH . WPINC . '/feed-atom-comments.php');
		else
			load_template( \Podlove\PLUGIN_DIR . 'templates/feed-atom.php' );
			
		exit;
	}
	
}