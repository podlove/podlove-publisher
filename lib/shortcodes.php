<?php
namespace Podlove;
use \Podlove\Model;

function handle_direct_download() {
	
	if ( ! isset( $_GET['download_media_file'] ) )
		return;

	$media_file_id = (int) $_GET['download_media_file'];
	$media_file    = Model\MediaFile::find_by_id( $media_file_id );

	if ( ! $media_file ) {
		status_header( 404 );
		exit;
	}

	$media_location = $media_file->media_location();

	if ( ! $media_location || ! $media_location->downloadable ) {
		status_header( 404 );
		exit;
	}

	// tell WP Super Cache to not cache download links
	if ( ! defined( 'DONOTCACHEPAGE' ) )
		define( 'DONOTCACHEPAGE', true );

	header( "Expires: 0" );
	header( 'Cache-Control: must-revalidate' );
    header( 'Pragma: public' );
	header( "Content-Type: application/force-download" );
	header( "Content-Description: File Transfer" );
	header( "Content-Disposition: attachment; filename=" . $media_file->get_download_file_name() );
	header( "Content-Transfer-Encoding: binary" );

	if ( $media_file->size > 0 )
		header( 'Content-Length: ' . $media_file->size );
	
	ob_clean();
	flush();
	readfile( $media_file->get_file_url() );
	exit;
}
add_action( 'init', '\Podlove\handle_direct_download' );

/**
 * Provides a shortcode to display all available download links.
 *
 * Usage:
 *	[podlove-episode-downloads]
 * 
 * @param  array $options
 * @return string
 */
function episode_downloads_shortcode( $options ) {
	global $post;

	if ( is_feed() )
		return '';

	$episode = Model\Episode::find_or_create_by_post_id( $post->ID );
	$media_files = $episode->media_files();

	$html = '<ul class="episode_download_list">';
	foreach ( $media_files as $media_file ) {

		$media_location = $media_file->media_location();

		if ( ! $media_location->downloadable )
			continue;

		$media_format   = $media_location->media_format();
		
		$download_link_url  = get_bloginfo( 'url' ) . '?download_media_file=' . $media_file->id;
		$download_link_name = str_replace( " ", "&nbsp;", $media_location->title );

		$html .= '<li class="' . $media_format->extension . '">';
		$html .= sprintf(
			'<a href="%s">%s%s</a>',
			apply_filters( 'podlove_download_link_url', $download_link_url, $media_file ),
			apply_filters( 'podlove_download_link_name', $download_link_name, $media_file ),
			'<span class="size">' . \Podlove\format_bytes( $media_file->size, 0 ) . '</span>'
		);
		$html .= '</li>';
	}
	$html .= '</ul>';

	return apply_filters( 'podlove_downloads_before', '' )
	     . $html
	     . apply_filters( 'podlove_downloads_after', '' );
}
add_shortcode( 'podlove-episode-downloads', '\Podlove\episode_downloads_shortcode' );

/**
 * Provides shortcode to display web player.
 *
 * Right now there is only audio support.
 *
 * Usage:
 * 	[podlove-web-player]
 * 	
 * @param  array $options
 * @return string
 */
function webplayer_shortcode( $options ) {
	global $post;

	$episode = Model\Episode::find_or_create_by_post_id( $post->ID );
	$podcast = Model\Podcast::get_instance();

	$formats_data = get_option( 'podlove_webplayer_formats' );

	if ( ! count( $formats_data ) )
		return;

	$available_formats = array();
	$audio_formats = array( 'mp3', 'mp4', 'ogg' );

	foreach ( $audio_formats as $audio_format ) {
		$media_location = Model\MediaLocation::find_by_id( $formats_data['audio'][ $audio_format ] );

		if ( ! $media_location )
			continue;

		$media_file = Model\MediaFile::find_by_episode_id_and_media_location_id( $episode->id, $media_location->id );

		if ( $media_file )
			$available_formats[] = sprintf( '%s="%s"', $audio_format, $media_file->get_file_url() );
	}

	$chapters = '';
	if ( $episode->chapters ) {
		$chapters = 'chapters="_podlove_chapters"';
	}

	return do_shortcode( '[podloveaudio ' . implode( ' ', $available_formats ) . ' ' . $chapters . ']' );
}
add_shortcode( 'podlove-web-player', '\Podlove\webplayer_shortcode' );

$podlove_public_episode_attributes = array( 'subtitle', 'summary', 'slug', 'duration', 'chapters' );
foreach ( $podlove_public_episode_attributes as $attr ) {
	add_shortcode( 'podlove-episode-' . $attr, function() use ( $attr ) {
		global $post;
		return nl2br( Model\Episode::find_or_create_by_post_id( $post->ID )->$attr );
	} );
}