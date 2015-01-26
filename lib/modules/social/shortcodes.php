<?php
namespace Podlove\Modules\Social;

class Shortcodes
{
	public static function init() {
		add_shortcode( 'podlove-podcast-social-media-list', array( __CLASS__, 'social_media_list') );
		add_shortcode( 'podlove-podcast-donations-list',    array( __CLASS__, 'podcast_donations_list') );
	}

	/**
	 * [podlove-podcast-social-media-list] shortcode
	 */
	public static function social_media_list() {
		return \Podlove\Template\TwigFilter::apply_to_html('@social/podcast-social-media-list.twig');
	}

	/**
	 * [podlove-podcast-donations-list] shortcode
	 */
	public static function podcast_donations_list() {
		return \Podlove\Template\TwigFilter::apply_to_html('@social/podcast-donations-list.twig');
	}
}