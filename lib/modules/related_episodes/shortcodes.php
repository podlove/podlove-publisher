<?php
namespace Podlove\Modules\RelatedEpisodes;

class Shortcodes {

	public static function init() {
		add_shortcode('podlove-related-episodes', [__CLASS__, 'related_episodes']);
	}

	/**
	 * Related Episodes Shortcode
	 * 
	 * @param  array  $args    List of arguments. (none supported)
	 * @param  string $content Optional shortcode content. If any is set it is inserted before the list. But only if there are entries.
	 * @return string
	 */
	public static function related_episodes($args = [], $content = '') {
		return \Podlove\Template\TwigFilter::apply_to_html('@related-episodes/related-episodes-list.twig', ['before' => $content]);
	}

}