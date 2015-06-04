<?php
namespace Podlove\Modules\Flattr\Template;

/**
 * Flattr Template Wrapper
 * 
 * Requires "Flattr" module.
 *
 * @templatetag flattr
 */
class Flattr extends \Podlove\Template\Wrapper {

	public function __construct() {
	}

	protected function getExtraFilterArgs() {
		return [];
	}

	// /////////
	// Accessors
	// /////////
	
	/**
	 * Flattr Button
	 * 
	 * **Parameters**
	 * 
	 * - **url:** URL of thing to flattr. Defaults to WordPress permalink.
	 * - **style:** Button style."large", "compact" or "static". Default: "compact".
	 * - **user:** Flattr user id. Defaults to Flattr account in podcast settings.
	 * 
	 * **Examples**
	 * 
	 * Simple button with defaults
	 * 
	 * ```jinja
	 * {{ flattr.button }}
	 * ```
	 * 
	 * Large button
	 * 
	 * ```jinja
	 * {{ flattr.button({ style: 'large' }) }}
	 * ```
	 * 
	 * Button for the Podlove Publisher plugin
	 * 
	 * ```jinja
	 * {{ flattr.button({ user: 'ericteubert', url: 'http://wordpress.org/extend/plugins/podlove-podcasting-plugin-for-wordpress/' }) }}
	 * ```
	 * 
	 * @accessor
	 */
	public function button($args = []) {
		
		$defaults = [
			'url'   => get_permalink(),
			'style' => 'compact',
			'user'  => \Podlove\Model\Podcast::get()->flattr
		];
		$args = wp_parse_args($args, $defaults);

		if ($args['style'] == 'static') {
			return self::static_button($args['url'], $args['user']);
		} else {
			return self::dynamic_button($args);
		}
	}

	private static function dynamic_button($args) {
		
		$description = __('Flattr this', 'podlove');

		return sprintf(
			'<a class="FlattrButton" style="display:none;" href="%s" title="%s" 
				data-flattr-uid="%s" %s>%s</a>',
			$args['url'], 
			$description, 
			$args['user'], 
			$args['style'] == 'compact' ? 'data-flattr-button="compact"' : '',
			$description
		);
	}

	private static function static_button($url, $uid) {
		return sprintf(
			'<a href="https://flattr.com/submit/auto?user_id=%2$s&url=%1$s" target="_blank"><img src="//button.flattr.com/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0"></a>',
			urlencode($url),
			$uid
		);
	}
}