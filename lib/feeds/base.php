<?php
namespace Podlove\Feeds;
use Podlove\Model;

function the_description() {
	global $post;

	$episode  = \Podlove\Model\Episode::find_one_by_post_id( $post->ID );

	$summary  = trim( $episode->summary );
	$subtitle = trim( $episode->subtitle );
	$title    = trim( $post->post_title );

	$description = '';

	if ( strlen( $summary ) )
		$description = $summary;
	else if ( strlen( $subtitle ) )
		$description = $subtitle;
	else
		$description = $title;

	echo apply_filters( 'podlove_feed_item_description', $description );
}

function override_feed_title( $feed ) {
	add_filter( 'podlove_feed_title', function ( $title ) {
		return htmlspecialchars( Model\Podcast::get_instance()->title );
	} );
}

function override_feed_language( $feed ) {
	add_filter( 'pre_option_rss_language', function ( $language ) use ( $feed ) {
		$podcast = Model\Podcast::get_instance();
		return apply_filters( 'podlove_feed_language', ( $podcast->language ) ? $podcast->language : $language );
	} );
}

/**
 * Prepare content for display in feed.
 *
 * - Trim whitespace
 * - Convert special characters to HTML entities
 * 
 * @param  string $content
 * @return string
 */
function prepare_for_feed( $content ) {
	return trim( htmlspecialchars( $content ) );
}

function override_feed_head( $hook, $podcast, $feed, $format ) {

	$filter_hooks = array(
		'podlove_feed_itunes_author'  ,
		'podlove_feed_itunes_owner'   ,
		'podlove_feed_itunes_subtitle',
		'podlove_feed_itunes_keywords',
		'podlove_feed_itunes_summary' ,
		'podlove_feed_itunes_complete'
	);
	foreach ( $filter_hooks as $filter ) {
		add_filter( $filter, 'convert_chars' );
	}
	add_filter( 'podlove_feed_content', '\Podlove\Feeds\prepare_for_feed' );

	remove_action( $hook, 'the_generator' );
	add_action( $hook, function () use ( $hook ) {
		switch ( $hook ) {
			case 'rss2_head':
				$gen = '<generator>' . \Podlove\get_plugin_header( 'Name' ) . ' v' . \Podlove\get_plugin_header( 'Version' ) . '</generator>';
				break;
			case 'atom_head':
				$gen = '<generator uri="' . \Podlove\get_plugin_header( 'PluginURI' ) . '" version="' . \Podlove\get_plugin_header( 'Version' ) . '">' . \Podlove\get_plugin_header( 'Name' ) . '</generator>';
				break;
		}
		echo $gen;
	} );

	add_action( $hook, function () use ( $feed ) {
		echo $feed->get_self_link();
		echo $feed->get_alternate_links();
	}, 9 );
	
	add_action( $hook, function () use ( $podcast, $feed, $format ) {
		echo PHP_EOL;

		$author = "\t" . sprintf( '<itunes:author>%s</itunes:author>', $podcast->author_name );
		echo apply_filters( 'podlove_feed_itunes_author', $author );
		echo PHP_EOL;

		$summary = "\t" . sprintf( '<itunes:summary>%s</itunes:summary>', $podcast->summary );
		echo apply_filters( 'podlove_feed_itunes_summary', $summary );
		echo PHP_EOL;

		$categories = \Podlove\Itunes\categories( false );	
		$category_html = '';
		for ( $i = 1; $i <= 3; $i++ ) { 
			$category_id = $podcast->{'category_' . $i};

			if ( ! $category_id )
				continue;

			list( $cat, $subcat ) = explode( '-', $category_id );

			if ( $subcat == '00' ) {
				$category_html .= sprintf(
					'<itunes:category text="%s"></itunes:category>',
					htmlspecialchars( $categories[ $category_id ] )
				);
			} else {
				$category_html .= sprintf(
					'<itunes:category text="%s"><itunes:category text="%s"></itunes:category></itunes:category>',
					htmlspecialchars( $categories[ $cat . '-00' ] ),
					htmlspecialchars( $categories[ $category_id ] )
				);
			}
		}
		echo apply_filters( 'podlove_feed_itunes_categories', $category_html );
		echo PHP_EOL;

		$owner = sprintf( '
	<itunes:owner>
		<itunes:name>%s</itunes:name>
		<itunes:email>%s</itunes:email>
	</itunes:owner>',
			$podcast->owner_name,
			$podcast->owner_email
		);
		echo "\t" . apply_filters( 'podlove_feed_itunes_owner', $owner );
		echo PHP_EOL;
		
		if ( $podcast->cover_image ) {
			$coverimage = sprintf( '<itunes:image href="%s" />', $podcast->cover_image );
		} else {
			$coverimage = '';
		}
		echo "\t" . apply_filters( 'podlove_feed_itunes_image', $coverimage );
		echo PHP_EOL;

		$subtitle = sprintf( '<itunes:subtitle>%s</itunes:subtitle>', $podcast->subtitle );
		echo "\t" . apply_filters( 'podlove_feed_itunes_subtitle', $subtitle );
		echo PHP_EOL;

		$keywords = sprintf( '<itunes:keywords>%s</itunes:keywords>', $podcast->keywords );
		echo "\t" . apply_filters( 'podlove_feed_itunes_keywords', $keywords );
		echo PHP_EOL;

		$block = sprintf( '<itunes:block>%s</itunes:block>', ( $feed->enable ) ? 'no' : 'yes' );
		echo "\t" . apply_filters( 'podlove_feed_itunes_block', $block );
		echo PHP_EOL;

        $explicit = sprintf( '<itunes:explicit>%s</itunes:explicit>', ( $podcast->explicit == 2) ? 'clean' : ( ( $podcast->explicit ) ? 'yes' : 'no' ) );
		echo "\t" . apply_filters( 'podlove_feed_itunes_explicit', $explicit );
		echo PHP_EOL;

		$keywords = sprintf( '<itunes:complete>%s</itunes:complete>', ( $podcast->complete ) ? 'yes' : 'no' );
		echo "\t" . apply_filters( 'podlove_feed_itunes_complete', $keywords );
		echo PHP_EOL;
	} );
}

function override_feed_entry( $hook, $podcast, $feed, $format ) {
	add_action( $hook, function () use ( $podcast, $feed, $format ) {
		global $post;

		$episode = Model\Episode::find_one_by_post_id( $post->ID );
		$asset   = $feed->episode_asset();
		$file    = Model\MediaFile::find_by_episode_id_and_episode_asset_id( $episode->id, $asset->id );
		$asset_assignment = Model\AssetAssignment::get_instance();

		if ( ! $file )
			return;

		$enclosure_file_size = $file->size;
		$cover_art_url       = $episode->get_cover_art();

		$enclosure_url = $episode->enclosure_url( $feed->episode_asset() );

		$chapters = new \Podlove\Feeds\Chapters( $episode );
		$chapters->render( 'inline' );

		$deep_link = Model\Feed::get_link_tag(array(
			'prefix' => 'atom',
			'rel'    => 'http://podlove.org/deep-link',
			'type'   => '',
			'title'  => '',
			'href'   => get_permalink() . "#"
		));
		echo apply_filters( 'podlove_deep_link', $deep_link, $feed );
		
		echo apply_filters( 'podlove_feed_enclosure', '', $enclosure_url, $enclosure_file_size, $format->mime_type );

		$duration = sprintf( '<itunes:duration>%s</itunes:duration>', $episode->get_duration( 'HH:MM:SS' ) );
		echo apply_filters( 'podlove_feed_itunes_duration', $duration );

		$author = apply_filters( 'podlove_feed_content', $podcast->author_name );
		$author = sprintf( '<itunes:author>%s</itunes:author>', $author );
		echo apply_filters( 'podlove_feed_itunes_author', $author );

		$subtitle = apply_filters( 'podlove_feed_content', $episode->subtitle );
		$subtitle = sprintf( '<itunes:subtitle>%s</itunes:subtitle>', $subtitle )  ;
		echo apply_filters( 'podlove_feed_itunes_subtitle', $subtitle );

		$summary = apply_filters( 'podlove_feed_content', strip_tags( $episode->summary ) );
		$summary = sprintf( '<itunes:summary>%s</itunes:summary>', $summary );
		echo apply_filters( 'podlove_feed_itunes_summary', $summary );

		if ( $cover_art_url ) {
			$cover_art = sprintf( '<itunes:image href="%s" />', $cover_art_url );
		} else {
			$cover_art = '';
		}
		echo apply_filters( 'podlove_feed_itunes_image', $cover_art );

		if ( $feed->embed_content_encoded ) {
			add_filter( 'the_content_feed', function( $content, $feed_type ) {
				return preg_replace('#<style(.*?)>(.*?)</style>#is', '', $content );
			}, 10, 2 );
			$content_encoded = '<content:encoded><![CDATA[' . get_the_content_feed( 'rss2' ) . ']]></content:encoded>';
			echo apply_filters( 'podlove_feed_content_encoded', $content_encoded );
		}

	}, 11 );
}
