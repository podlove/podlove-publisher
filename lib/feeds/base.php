<?php

namespace Podlove\Feeds;

function mute_feed_title() {
	add_filter( 'bloginfo_rss', function ( $value, $key ) {
		return apply_filters( 'podlove_feed_title_name', ( $key == 'name' ) ? '' : $value );
	}, 10, 2 );
}

function override_feed_title( $feed ) {
	add_filter( 'wp_title_rss', function ( $title ) use ( $feed ) {
		return apply_filters( 'podlove_feed_title', htmlspecialchars( $feed->title ) );
	} );
}

function override_feed_language( $feed ) {
	add_filter( 'option_rss_language', function ( $language ) use ( $feed ) {
		return apply_filters( 'podlove_feed_language', ( $feed->language ) ? $feed->language : $language );
	} );
}

function override_feed_head( $hook, $show, $feed, $format ) {
	add_action( $hook, function () use ( $show, $feed, $format ) {
		$author = sprintf( '<itunes:author>%s</itunes:author>', $show->author_name );
		echo apply_filters( 'podlove_feed_itunes_author', $author );

		$summary = sprintf( '<itunes:summary>%s</itunes:summary>', $show->summary );
		echo apply_filters( 'podlove_feed_itunes_summary', $summary );

		$categories = \Podlove\Itunes\categories( false );	
		$category_html = '';
		for ( $i = 1; $i <= 3; $i++ ) { 
			$category_id = $show->{'category_' . $i};

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

		$owner = sprintf( '
			<itunes:owner>
				<itunes:name>%s</itunes:name>
				<itunes:email>%s</itunes:email>
			</itunes:owner>',
			$show->owner_name,
			$show->owner_email
		);
		echo apply_filters( 'podlove_feed_itunes_owner', $owner );

		$subtitle = sprintf( '<itunes:subtitle>%s</itunes:subtitle>', $show->subtitle );
		echo apply_filters( 'podlove_feed_itunes_subtitle', $subtitle );

		$keywords = sprintf( '<itunes:keywords>%s</itunes:keywords>', $show->keywords );
		echo apply_filters( 'podlove_feed_itunes_keywords', $keywords );

		$block = sprintf( '<itunes:block>%s</itunes:block>', ( $feed->block ) ? 'yes' : 'no' );
		echo apply_filters( 'podlove_feed_itunes_block', $block );

		// @todo support "clean" tag
		$explicit = sprintf( '<itunes:explicit>%s</itunes:explicit>', ( $show->explicit ) ? 'yes' : 'no' );
		echo apply_filters( 'podlove_feed_itunes_explicit', $explicit );
	} );
}

function override_feed_entry( $hook, $show, $feed, $format ) {
	add_action( $hook, function () use ( $show, $feed, $format ) {
		global $post;

		$meta      = get_post_meta( $post->ID, '_podlove_meta', true );
		$show_meta = $meta[ $show->id ];

		// FIXME file size must be file format specific!
		$enclosure_duration  = isset( $show_meta[ 'duration' ] ) ? $show_meta[ 'duration' ] : 0;
		$enclosure_file_size = isset( $show_meta[ 'file_size' ] ) ? $show_meta[ 'file_size' ] : 0;
		$file_slug           = isset( $show_meta[ 'file_slug' ] ) ? $show_meta[ 'file_slug' ] : NULL;
		$cover_art_url       = isset( $show_meta[ 'cover_art_url' ] ) ? $show_meta[ 'cover_art_url' ] : NULL;

		if ( ! $file_slug ) {
			// TODO might be a good idea to notify the podcast admin
		}

		$enclosure_url  = $show->media_file_base_uri;
		$enclosure_url .= $file_slug;
		$enclosure_url .= $format->slug;
		$enclosure_url .= '.';
		$enclosure_url .= $format->extension;
		
		echo apply_filters( 'podlove_feed_enclosure', '', $enclosure_url, $enclosure_file_size, $format->mime_type );

		$duration = sprintf( '<itunes:duration>%s</itunes:duration>', $enclosure_duration );
		echo apply_filters( 'podlove_feed_itunes_duration', $duration );

		$author = sprintf( '<itunes:author>%s</itunes:author>', $show->author_name );
		echo apply_filters( 'podlove_feed_itunes_author', $author );

		$summary = sprintf( '<itunes:summary>%s</itunes:summary>', htmlspecialchars( strip_tags( $post->post_excerpt ) ) );
		echo apply_filters( 'podlove_feed_itunes_summary', $summary );

		$cover_art = sprintf( '<itunes:image href="%s" />', $cover_art_url );
		echo apply_filters( 'podlove_feed_itunes_image', $cover_art );
	} );
}