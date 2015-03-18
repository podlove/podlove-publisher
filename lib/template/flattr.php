<?php
namespace Podlove\Template;

/**
 * Flattr Template Wrapper
 *
 * @templatetag flattr
 */
class Flattr extends Wrapper {

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
	 * - url: URL of thing to flattr. Defaults to WordPress permalink.
	 * - button: Button style."large", "compact" or "static". Default: "compact".
	 * - uid: Flattr user id. Defaults to Flattr account in podcast settings.
	 * 
	 * **Examples**
	 * 
	 * Simple button with defaults
	 * 
	 * ```
	 * {{ flattr.button }}
	 * ```
	 * 
	 * Large button
	 * 
	 * ```
	 * {{ flattr.button({ button: 'large' }) }}
	 * ```
	 * 
	 * Button for the Podlove Publisher plugin
	 * 
	 * ```
	 * {{ flattr.button({ uid: 'ericteubert', url: 'http://wordpress.org/extend/plugins/podlove-podcasting-plugin-for-wordpress/' }) }}
	 * ```
	 * 
	 * @accessor
	 */
	public function button($args = []) {
		
		$defaults = [
			'url'   => get_permalink(),
			'button' => 'compact',
			'uid'   => \Podlove\Model\Podcast::get()->flattr
		];
		$args = wp_parse_args($args, $defaults);

		if ($args['button'] == 'static') {
			return self::static_button($args['url'], $args['uid']);
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
			$args['uid'], 
			$args['button'] == 'compact' ? 'data-flattr-button="compact"' : '',
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

	private static function flattr_uuid() {
		return 'fb' . bin2hex(openssl_random_pseudo_bytes(5));
	}
}