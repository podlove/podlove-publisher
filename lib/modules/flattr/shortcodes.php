<?php
namespace Podlove\Modules\Flattr;

use \Podlove\Modules\Flattr\Flattr;

class Shortcodes {

	public static function init() {
		/**
		 * Parameters:
		 * 	- style: Button style."large", "compact" or "static". Default: "compact".
		 */
		add_shortcode('podlove-podcast-flattr-button', [__CLASS__, 'podcast_flattr_button']);

		/**
		 * Parameters:
		 * 	- style: Button style."large", "compact" or "static". Default: "compact".
		 */
		add_shortcode('podlove-episode-flattr-button', [__CLASS__, 'episode_flattr_button']);
	}

	public static function podcast_flattr_button($args = []) {
		return (new Template\Flattr)->button([
			'url'   => \Podlove\get_landing_page_url(),
			'style' => isset($args['style']) ? $args['style'] : 'compact',
			'user'  => Flattr::get_setting('account'),
		]);
	}

	public static function episode_flattr_button($args = []) {
		return (new Template\Flattr)->button([
			'url'   => get_permalink(),
			'style' => isset($args['style']) ? $args['style'] : 'compact',
			'user'  => Flattr::get_setting('account'),
		]);
	}

}