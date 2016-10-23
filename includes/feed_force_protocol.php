<?php 
use Podlove\Model\Feed;

add_filter('podlove_enclosure_url', 'podlove_maybe_force_feed_protocol');
add_filter('podlove_image_url', 'podlove_maybe_force_feed_protocol');

function podlove_maybe_force_feed_protocol($url) {

	$scheme = \Podlove\Model\Podcast::get()->feed_force_protocol;

	// stop if default setting is used
	if (!in_array($scheme, ['http', 'https'])) {
		return $url;
	}

	// ignore non-publisher feeds
	if (!is_publisher_feed()) {
		return $url;
	}

	$url = set_url_scheme($url, $scheme);

	return $url;
}

function is_publisher_feed()
{
	global $wpdb;
	$feed_slugs = $wpdb->get_col("SELECT slug FROM " . Feed::table_name());

	// remove empty
	$feed_slugs = array_filter($feed_slugs);

	if (empty($feed_slugs)) {
		return false;
	}

	return is_feed($feed_slugs);
}
