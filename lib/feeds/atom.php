<?php
namespace Podlove\Feeds;
use \Podlove\Model;

require_once \Podlove\PLUGIN_DIR  . '/lib/feeds/base.php';

class Atom {
	
	public function __construct( $feed_slug ) {
		
		add_action( 'atom_ns', function () {
			echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" ';
			echo 'xmlns:psc="http://podlove.org/simple-chapters" ';
		} );
		
		add_filter( 'feed_link', function ( $output, $feed ) use ( $feed_slug ) {
			return get_bloginfo( 'url' ) . '/feed/' . $feed_slug . '/';
		}, 10, 2 );
		
		$podcast        = Model\Podcast::get_instance();
		$feed           = Model\Feed::find_one_by_slug( $feed_slug );
		$episode_asset  = $feed->episode_asset();
		$file_type      = $episode_asset->file_type();

		add_filter( 'podlove_feed_enclosure', function ( $enclosure, $enclosure_url, $enclosure_file_size, $mime_type ) {
			
			if ( $enclosure_file_size < 0 )
				$enclosure_file_size = 0;

			return sprintf( '<link rel="enclosure" href="%s" length="%s" type="%s"/>', $enclosure_url, $enclosure_file_size, $mime_type );
		}, 10, 4 );

		override_feed_title( $feed );
		override_feed_language( $feed );
		override_feed_head( 'atom_head', $podcast, $feed, $file_type );
		override_feed_entry( 'atom_entry', $podcast, $feed, $file_type );

		add_action( 'atom_entry', function () {
			if ( apply_filters( 'podlove_feed_show_summary', true ) ) {
				echo "<summary><![CDATA[";
				\Podlove\Feeds\the_description();
				echo "]]></summary>";
			}
		} );

		add_action( 'atom_author', function() use ( $podcast ) {

			$author = $podcast->author_name;
			if ( ! $author ) $author = $podcast->publisher_name;
			if ( ! $author ) $author = get_the_author();
			$author = apply_filters( 'podlove_feed_author_name', $author );

			$author_url = $podcast->publisher_url;
			if ( ! $author_url ) $author_url = get_the_author_meta( 'url' );
			$author_url = apply_filters( 'podlove_feed_author_url', $author_url );

			?>
			<name><?php echo $author; ?></name>
			<?php  if ( ! empty( $author_url ) ): ?>
				<uri><?php echo $author_url; ?></uri>
			<?php endif;
		} );

		$this->do_feed( $feed );
	}
	
	function do_feed( $feed ) {

		global $wp_query;

		$args = array(
			'post_type' => 'podcast',
			'post__in'   => $feed->post_ids()
		);
		$args = array_merge( $wp_query->query_vars, $args );
		query_posts( $args );
		
		if ( $wp_query->is_comment_feed )
			load_template( ABSPATH . WPINC . '/feed-atom-comments.php');
		else
			load_template( \Podlove\PLUGIN_DIR . 'templates/feed-atom.php' );
			
		exit;
	}
	
}