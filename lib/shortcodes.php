<?php
namespace Podlove;
use \Podlove\Model;

function handle_direct_download() {
	
	if ( ! isset( $_GET['download_media_file'] ) )
		return;

	// tell WP Super Cache to not cache download links
	if ( ! defined( 'DONOTCACHEPAGE' ) )
		define( 'DONOTCACHEPAGE', true );

	// if download_media_file is a URL, download directly
	if ( filter_var( $_GET['download_media_file'], FILTER_VALIDATE_URL ) ) {
		$parsed_url = parse_url($_GET['download_media_file']);
		$file_name = substr( $parsed_url['path'], strrpos( $parsed_url['path'], "/" ) + 1 );
		header( "Expires: 0" );
		header( 'Cache-Control: must-revalidate' );
	    header( 'Pragma: public' );
		header( "Content-Type: application/x-bittorrent" );
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=$file_name" );
		header( "Content-Transfer-Encoding: binary" );
		ob_clean();
		flush();
		while ( @ob_end_flush() ); // flush and end all output buffers
		readfile( $_GET['download_media_file'] );
		exit;
	}

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

	if ( \Podlove\get_setting('website', 'force_download') == 'on' && in_array( strtolower( ini_get( 'allow_url_fopen' ) ), array( "1", "on", "true" ) ) ) {
		header( "Expires: 0" );
		header( 'Cache-Control: must-revalidate' );
	    header( 'Pragma: public' );
		header( "Content-Type: " . $episode_asset->file_type()->mime_type );
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

		if ( ! $media_file->is_valid() )
			continue;

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
		$html = '<form action="' . get_bloginfo( 'url' ) . '">';
		$html.= '<div class="episode_downloads">';
		$html.= 	'<select name="download_media_file">';
		foreach ( $downloads as $download ) {
			$html .= sprintf(
				'<option value="%d" data-raw-url="%s">%s [%s]</option>',
				$download['file']->id,
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
		$html.= '</form>';
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

	if ( is_feed() )
		return '';

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
		'autop' => false
	);

	$attributes = array_merge( $defaults, $attributes );

	if ( $attributes['title'] !== '' )
		_deprecated_argument( __FUNCTION__, '1.3.14-alpha', 'The "title" attribute for [podlove-template] shortcode is deprecated. Use "id" instead.' );

	// backward compatibility
	$template_id = $attributes['id'] ? $attributes['id'] : $attributes['title'];

	if ( ! $template = Model\Template::find_one_by_title( $template_id ) )
		return sprintf( __( 'Podlove Error: Whoops, there is no template with id "%s"', 'podlove' ), $template_id );

	$html = apply_filters('podlove_template_raw', $template->title, $attributes);

	// apply autop and shortcodes
	if ( in_array( $attributes['autop'], array('yes', 1, 'true') ) )
		$html = wpautop( $html );

	$html = do_shortcode( $html );

	return $html;
}
add_shortcode( 'podlove-template', '\Podlove\template_shortcode' );

add_filter('podlove_template_raw', array('\Podlove\Template\TwigFilter', 'apply_to_html'), 10, 2);

function podcast_license() {
	$podcast = Model\Podcast::get_instance();
		return $podcast->get_license_html();
}
add_shortcode( 'podlove-podcast-license', '\Podlove\podcast_license' );


function episode_license() {
	global $post;

	if ( is_feed() )
		return '';

	$episode = Model\Episode::find_or_create_by_post_id( $post->ID );
	return $episode->get_license_html();
}
add_shortcode( 'podlove-episode-license', '\Podlove\episode_license' );

function feed_list() {
	return \Podlove\Template\TwigFilter::apply_to_html('@core/shortcode/feed-list.twig');
}
add_shortcode( 'podlove-feed-list', '\Podlove\feed_list' );

function episode_list() {
	return \Podlove\Template\TwigFilter::apply_to_html('@core/shortcode/episode-list.twig');
}
add_shortcode( 'podlove-episode-list', '\Podlove\episode_list' );