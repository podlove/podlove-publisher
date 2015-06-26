<?php
namespace Podlove\Modules\Flattr;

/**
 * Add Flattr payment information to podcast feeds.
 */
class FeedExtension {

	public static function init() {
		add_action('podlove_append_to_feed_entry', [__CLASS__, 'extend_item'], 10, 4);
		add_action('podlove_append_to_feed_head',  [__CLASS__, 'extend_head'], 10, 3);
	}

	public static function extend_head($podcast, $feed, $format) {

		$url = sprintf(
			'https://flattr.com/submit/auto?user_id=%s&amp;language=%s&amp;url=%s&amp;title=%s&amp;description=%s',
			urlencode(Flattr::get_setting("account")),
			urlencode(str_replace("-", "_", $podcast->language)),
			urlencode($podcast->landing_page_url()),
			urlencode($podcast->title),
			urlencode($podcast->subtitle)
		);

		echo sprintf('<atom:link rel="payment" title="Flattr this!" href="%s" type="text/html" />', $url);
	}

	public static function extend_item($podcast, $episode, $feed, $format) {
		
		$url = sprintf(
			'https://flattr.com/submit/auto?user_id=%s&amp;language=%s&amp;url=%s&amp;title=%s&amp;description=%s',
			urlencode(Flattr::get_setting("account")),
			urlencode(str_replace("-", "_", $podcast->language)),
			urlencode($episode->permalink()),
			urlencode($episode->title()),
			urlencode($episode->description())
		);
		
		echo sprintf('<atom:link rel="payment" title="Flattr this!" href="%s" type="text/html" />', $url);
	}
}