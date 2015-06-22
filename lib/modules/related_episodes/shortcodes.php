<?php
namespace Podlove\Modules\RelatedEpisodes;

class Shortcodes {

	public static function init() {
		add_shortcode('podlove-related-episodes', [__CLASS__, 'related_episodes']);
	}

	public static function related_episodes($args = []) {
		return \Podlove\Template\TwigFilter::apply_to_html('@related-episodes/related-episodes-list.twig');
	}

}