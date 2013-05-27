<?php
namespace Podlove;
use \Podlove\Model;

function handle_direct_download() {
	
	if ( ! isset( $_GET['download_media_file'] ) )
		return;

	// tell WP Super Cache to not cache download links
	if ( ! defined( 'DONOTCACHEPAGE' ) )
		define( 'DONOTCACHEPAGE', true );

	$media_file_id = (int) $_GET['download_media_file'];
	$media_file    = Model\MediaFile::find_by_id( $media_file_id );

	if ( ! $media_file ) {
		status_header( 404 );
		exit;
	}

	$episode_asset = $media_file->episode_asset();

	if ( ! $episode_asset || ! $episode_asset->downloadable ) {
		status_header( 404 );
		exit;
	}

	if ( in_array( strtolower( ini_get( 'allow_url_fopen' ) ), array( "1", "on", "true" ) ) ) {
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
		while ( @ob_end_flush() ); // flush and end all output buffers
		readfile( $media_file->get_file_url() );
		exit;
	} else {
		header( "Location: " . $media_file->get_file_url() );
		exit;
	}
}
add_action( 'init', '\Podlove\handle_direct_download' );

/**
 * Provides a shortcode to display all available download links.
 *
 * Usage:
 *	[podlove-episode-downloads]
 *
 *  Attributes:
 *    style  "buttons" - list of buttons
 *           "select" (default) - html select list
 * 
 * @param  array $options
 * @return string
 */
function episode_downloads_shortcode( $options ) {
	global $post;

	if ( is_feed() )
		return '';

	$defaults = array( 'style' => 'select' );
	$attributes = shortcode_atts( $defaults, $options );

	$episode = Model\Episode::find_or_create_by_post_id( $post->ID );
	$media_files = $episode->media_files();
	$downloads = array();

	foreach ( $media_files as $media_file ) {

		$episode_asset = $media_file->episode_asset();

		if ( ! $episode_asset->downloadable )
			continue;

		$file_type = $episode_asset->file_type();
		
		$download_link_url  = get_bloginfo( 'url' ) . '?download_media_file=' . $media_file->id;
		$download_link_name = str_replace( " ", "&nbsp;", $episode_asset->title );

		$downloads[] = array(
			'url'  => $download_link_url,
			'name' => $download_link_name,
			'size' => \Podlove\format_bytes( $media_file->size, 0 ),
			'file' => $media_file
		);
	}

	if ( $attributes['style'] === 'buttons' ) {
		$html = '<ul class="episode_download_list">';
		foreach ( $downloads as $download ) {
			$html .= '  <li>';
			$html .= sprintf(
				'<a href="%s">%s%s</a>',
				apply_filters( 'podlove_download_link_url', $download['url'], $download['file'] ),
				apply_filters( 'podlove_download_link_name', $download['name'], $download['file'] ),
				'<span class="size">' . $download['size'] . '</span>'
			);
			$html .= '  </li>';
		}
		$html .= '</ul>';
	} else {
		$html = '<div class="episode_downloads">';
		$html.= 	'<select name="podlove_downloads">';
		foreach ( $downloads as $download ) {
			$html .= sprintf(
				'<option value="%s" data-raw-url="%s">%s [%s]</option>',
				apply_filters( 'podlove_download_link_url', $download['url'], $download['file'] ),
				$download['file']->get_file_url(),
				apply_filters( 'podlove_download_link_name', $download['name'], $download['file'] ),
				$download['size']
			);
		}
		$html.= 	'</select>';
		$html.= 	'<button class="primary">Download</button>';
		$html.= 	'<button class="secondary">Show URL</button>';
		// $html.= 	'<a href="#">Show URL</a>';
		$html.= '</div>';
	}

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
	$printer = new \Podlove\Modules\PodloveWebPlayer\Printer( $episode );
	return $printer->render();
}
add_shortcode( 'podlove-web-player', '\Podlove\webplayer_shortcode' );

/**
 * @deprecated since 1.2.18-alpha
 */
$podlove_public_episode_attributes = array( 'subtitle', 'summary', 'slug', 'duration', 'chapters' );
foreach ( $podlove_public_episode_attributes as $attr ) {
	add_shortcode( 'podlove-episode-' . $attr, function() use ( $attr ) {
		global $post;
		return nl2br( Model\Episode::find_or_create_by_post_id( $post->ID )->$attr );
	} );
}

function episode_data_shortcode( $attributes ) {
	global $post;

	$defaults = array(
		'field' => '',
		'format' => 'HH:MM:SS',
		'date_format' => get_option( 'date_format' ) . ' ' . get_option( 'time_format' )
	);
	$attributes = shortcode_atts( $defaults, $attributes );

	$episode = Model\Episode::find_or_create_by_post_id( $post->ID );
	if ( ! $episode )
		return;

	$allowed_fields = array( 'subtitle', 'summary', 'slug', 'chapters' );

	if ( in_array( $attributes['field'], $allowed_fields ) ) {
		return nl2br( $episode->$attributes['field'] );
	} elseif ( $attributes['field'] == 'image' ) {
		return $episode->get_cover_art();
	} elseif ( $attributes['field'] == 'duration' ) {
		return $episode->get_duration( $attributes['format'] );
	} elseif ( $attributes['field'] == 'title' ) {
		return get_the_title( $episode->post_id );
	} elseif ( stristr( $attributes['field'], '_date' ) !== false ) {
		return date( $attributes['date_format'], strtotime( $episode->$attributes['field'] ) );
	} else {
		return sprintf( __( 'Podlove Error: Unknown episode field "%s"', 'podcast' ), $attributes['field'] );
	}
}
add_shortcode( 'podlove-episode', '\Podlove\episode_data_shortcode' );

function podcast_data_shortcode( $attributes ) {

	$defaults = array( 'field' => '' );
	$attributes = shortcode_atts( $defaults, $attributes );

	$podcast = Model\Podcast::get_instance();

	if ( $podcast->has_property( $attributes['field'] ) ) {
		return $podcast->$attributes['field'];
	} else {
		return sprintf( __( 'Podlove Error: Unknown podcast field "%s"', 'podcast' ), $attributes['field'] );
	}
}
add_shortcode( 'podlove-podcast', '\Podlove\podcast_data_shortcode' );
add_shortcode( 'podlove-show', '\Podlove\podcast_data_shortcode' );

/**
 * Provides shortcode to display episode template.
 *
 * Usage:
 * 	
 * 	[podlove-template id="Template Title"]
 *
 * 	Parameters:
 * 		title: (required) Title of template to render.
 * 		autop: (optional) Wraps blocks of text in p tags. 'yes' or 'no'. Default: 'yes'
 * 	
 * @param  array $attributes
 * @return string
 */
function template_shortcode( $attributes ) {

	$defaults = array(
		'title' => '',
		'id' => '',
		'autop' => 'yes'
	);

	$attributes = shortcode_atts( $defaults, $attributes );

	if ( $attributes['title'] !== '' )
		_deprecated_argument( __FUNCTION__, '1.3.14-alpha', 'The "title" attribute for [podlove-template] shortcode is deprecated. Use "id" instead.' );

	// backward compatibility
	$template_id = $attributes['id'] ? $attributes['id'] : $attributes['title'];

	if ( ! $template = Model\Template::find_one_by_title( $template_id ) )
		return sprintf( __( 'Podlove Error: Whoops, there is no template with id "%s"', 'podlove' ), $template_id );

	$html = $template->content;

	if ( in_array( $attributes['autop'], array('yes', 1, 'true') ) )
		$html = wpautop( $html );

	$html = do_shortcode( $html );

	return $html;
}
add_shortcode( 'podlove-template', '\Podlove\template_shortcode' );
