<?php
namespace Podlove;
use \Podlove\Model;

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

	if ( $attributes['style'] === 'buttons' ) {
		return \Podlove\Template\TwigFilter::apply_to_html('@core/shortcode/downloads-buttons.twig');
	} else {
		return \Podlove\Template\TwigFilter::apply_to_html('@core/shortcode/downloads-select.twig');
	}
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
 * @deprecated since 1.10.0 use {{ episode.player }} instead
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

/**
 * @deprecated since 1.10.0, use {{ episode.<attribute> }} instead
 */
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

/**
 * @deprecated since 1.10.0, use {{ podcast.<attribute> instead }}
 */
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

/**
 * @deprecated since 1.10.0, use {{ episode.license }} or {{ podcast.license }} instead
 */
function podcast_license() {
	$podcast = Model\Podcast::get_instance();
		return $podcast->get_license_html();
}
add_shortcode( 'podlove-podcast-license', '\Podlove\podcast_license' );

/**
 * @deprecated since 1.10.0, use {{ episode.license }} or {{ podcast.license }} instead
 */
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