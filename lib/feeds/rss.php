<?php
namespace Podlove\Feeds;
use \Podlove\Model;

require_once \Podlove\PLUGIN_DIR  . '/lib/feeds/base.php';

class RSS {
	
	public static function prepare_feed( $feed_slug ) {
		global $wp_query;

		add_action( 'rss2_ns', function () {
			echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" ';
			echo 'xmlns:psc="http://podlove.org/simple-chapters" ';
			echo 'xmlns:content="http://purl.org/rss/1.0/modules/content/" ';
			echo 'xmlns:fh="http://purl.org/syndication/history/1.0" ';
		} );

		$podcast        = Model\Podcast::get();
		$feed           = Model\Feed::find_one_by_slug( $feed_slug );
		$episode_asset  = $feed->episode_asset();
		$file_type      = $episode_asset->file_type();

		add_filter( 'podlove_feed_enclosure', function ( $enclosure, $enclosure_url, $enclosure_file_size, $mime_type, $media_file ) {

			if ( $enclosure_file_size < 0 )
				$enclosure_file_size = 0;

			$dom = new \Podlove\DomDocumentFragment;
			$element = $dom->createElement('enclosure');

			$attributes = [
				'url'    => $enclosure_url,
				'length' => $enclosure_file_size,
				'type'   => $mime_type
			];

			$attributes = apply_filters('podlove_feed_enclosure_attributes', $attributes, $media_file);

			foreach ($attributes as $k => $v) {
				$element->setAttribute($k, $v);
			}

			$dom->appendChild($element);

			return (string) $dom;
		}, 10, 5 );

		override_feed_title( $feed );
		override_feed_description( $feed );
		override_feed_language( $feed );
		override_feed_head( 'rss2_head', $podcast, $feed, $file_type );
		override_feed_entry( 'rss2_item', $podcast, $feed, $file_type );

		add_action( 'rss2_item', function () {
			if ( apply_filters( 'podlove_feed_show_summary', true ) ) {
				echo "<description><![CDATA[";
				\Podlove\Feeds\the_description();
				echo "]]></description>";
			}
		}, 9 );

		add_action( 'rss2_head', function () use ( $podcast, $feed ) {
			global $wp_query;

			$current_page = ( get_query_var('paged') ) ? get_query_var('paged') : 1;

			$feed_url_for_page = function($page) use ($feed)
			{
				$url = $feed->get_subscribe_url();

				if ($page > 0) {
					$url .= '?paged=' . $page;
				}

				return $url;		
			};

			if ( $current_page < $wp_query->max_num_pages )
				echo "\n\t" . sprintf( '<atom:link rel="next" href="%s" />', $feed_url_for_page($current_page+1) );

			if ( $current_page > 2 ) {
				echo "\n\t" . sprintf( '<atom:link rel="prev" href="%s" />', $feed_url_for_page($current_page-1) );
			} elseif ( $current_page == 2 ) {
				echo "\n\t" . sprintf( '<atom:link rel="prev" href="%s" />', $feed_url_for_page(0) );
			}

			echo "\n\t" . sprintf( '<atom:link rel="first" href="%s" />', $feed_url_for_page(0) );

			if ( $wp_query->max_num_pages > 1 )
				echo "\n\t" . sprintf( '<atom:link rel="last" href="%s" />', $feed_url_for_page($wp_query->max_num_pages) );

			if ( $podcast->language )
				echo "\n\t" . '<language>' . $podcast->language . '</language>';

			do_action( 'podlove_rss2_head', $feed );

		}, 9 );

		$posts_per_page = $feed->limit_items == 0 ? get_option( 'posts_per_rss' ) : $feed->limit_items;

		if ($posts_per_page == Model\Feed::ITEMS_GLOBAL_LIMIT) {
			$posts_per_page = $podcast->limit_items;
		}

		// now override the option so WP core functions accessing the option get the "correct" value
		add_filter('pre_option_posts_per_rss', function($_) use ($posts_per_page) {
			return $posts_per_page;
		});

		$args = array(
			'post_type'      => 'podcast',
			'post__in'       => $feed->post_ids(),
			'posts_per_page' => $posts_per_page
		);

		# The theme "getnoticed" globally overrides post_types in pre_get_posts.
		# Fix: hook in after the theme and override it again.
		# It's not bad practice because I *really* only want episodes in this feed.
		add_action('pre_get_posts', function ($query) {
		    $query->set('post_type', 'podcast');
		}, 20);

		/**
		 * In feeds, WordPress ignores the 'posts_per_page' parameter 
		 * and overrides it with the 'posts_per_rss' option. So we need to
		 * override that option.
		 */
		add_filter( 'post_limits', function($limits) use ($feed) {
			$page = get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : 1;

			$max = $feed->get_post_limit_sql();
			$start = $max * ($page - 1);

			if ($max > 0) {
				return 'LIMIT ' . $start . ', ' . $max;
			} else {
				return '';
			}
		} );

		$args = array_merge( $wp_query->query_vars, $args );
		query_posts( $args );
	}

	public static function render() {
		global $wp_query;

		if ( $wp_query->is_comment_feed )
			load_template( ABSPATH . WPINC . '/feed-rss2-comments.php');
		else
			load_template( \Podlove\PLUGIN_DIR . 'templates/feed-rss2.php' );

		exit;
	}
	
}

