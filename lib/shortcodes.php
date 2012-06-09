<?php
namespace Podlove;

function handle_direct_download() {
	
	if ( ! isset( $_GET[ 'download_media_file' ] ) )
		return;

	$media_file_id = (int) $_GET[ 'download_media_file' ];
	$media_file    = Model\MediaFile::find_by_id( $media_file_id );

	if ( ! $media_file )
		return;

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

	$episode     = Model\Episode::find_or_create_by_post_id( $post->ID );
	$release     = $episode->release();
	$media_files = $release->media_files();

	$html = '<ul class="episode_download_list">';
	foreach ( $media_files as $media_file ) {

		$media_location = $media_file->media_location();
		$media_format   = $media_location->media_format();
		
		$download_link_url = get_bloginfo( 'url' ) . '?download_media_file=' . $media_file->id;

		$download_link_name = sprintf(
			__( 'Download %s' ),
			$media_format->name
		);

		$html .= '<li class="' . $media_format->extension . '">';
		$html .= sprintf(
			'<a href="%s">%s</a>',
			apply_filters( 'podlove_download_link_url', $download_link_url, $media_file ),
			apply_filters( 'podlove_download_link_name', $download_link_name, $media_file )
		);
		$html .= '</li>';
	}
	$html .= '</ul>';

	return $html;
}
add_shortcode( 'podlove-episode-downloads', '\Podlove\episode_downloads_shortcode' );