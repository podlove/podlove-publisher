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
	$permalink   = get_permalink();

	/**
	 * Cache key must be unique for *every permutation* of the content.
	 * Meaning: If there are context based conditionals, the key must reflect them.
	 */
	$tag_permutation = implode('', array_map(function($tag) {
		return $tag() ? "1" : "0";
	}, \Podlove\Template\TwigFilter::$template_tags));

	$cache_key = $template_id . $permalink . $tag_permutation;
	$cache_key = apply_filters( 'podlove_template_shortcode_cache_key', $cache_key, $template_id );

	$cache = \Podlove\Cache\TemplateCache::get_instance();
	return $cache->cache_for($cache_key, function() use ($template_id, $attributes) {

		if (!$template = Model\Template::find_one_by_title_with_fallback($template_id))
			return sprintf( __( 'Podlove Error: Whoops, there is no template with id "%s"', 'podlove' ), $template_id );

		$html = apply_filters('podlove_template_raw', $template->title, $attributes);

		// apply autop and shortcodes
		if ( in_array($attributes['autop'], array('yes', 1, 'true')))
			$html = wpautop($html);

		return do_shortcode($html);
	});
}
add_shortcode( 'podlove-template', '\Podlove\template_shortcode' );

add_filter('podlove_template_raw', array('\Podlove\Template\TwigFilter', 'apply_to_html'), 10, 2);

function feed_list() {
	return \Podlove\Template\TwigFilter::apply_to_html('@core/shortcode/feed-list.twig');
}
add_shortcode( 'podlove-feed-list', '\Podlove\feed_list' );

function episode_list() {
	return \Podlove\Template\TwigFilter::apply_to_html('@core/shortcode/episode-list.twig');
}
add_shortcode( 'podlove-episode-list', '\Podlove\episode_list' );