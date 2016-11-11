<?php 
use Podlove\Model\Feed;

// change URLs within the feed
add_filter('podlove_enclosure_url', 'podlove_maybe_force_feed_internal_urls_protocol');
add_filter('podlove_image_url', 'podlove_maybe_force_feed_internal_urls_protocol');

// change the URLs linking to the feed
add_filter('feed_link', 'podlove_maybe_force_feed_url_protocol', 10, 2);

function podlove_force_feed_url_protocol($url)
{
	if (\Podlove\Feeds\feed_should_be_http()) {
		return set_url_scheme($url, 'http');
	} else {
		return $url;
	}
}

function podlove_maybe_force_feed_url_protocol($url, $feed) 
{
	// stop if the $feed slug does not belong to the Publisher	
	if (!is_publisher_feed($feed)) {
		return $url;
	}

	return podlove_force_feed_url_protocol($url);
}

function podlove_maybe_force_feed_internal_urls_protocol($url) 
{
	// stop if we are not in a Publisher feed
	if (!is_publisher_feed()) {
		return $url;
	}

	return podlove_force_feed_url_protocol($url);
}

function is_publisher_feed($slug = NULL)
{
	global $wpdb;
	$feed_slugs = $wpdb->get_col("SELECT slug FROM " . Feed::table_name());

	// remove empty
	$feed_slugs = array_filter($feed_slugs);

	if (empty($feed_slugs)) {
		return false;
	}

	// if no slug is given, check if we are in any Publisher feed context
	if ($slug === NULL) {
		return is_feed($feed_slugs);
	} else {
		// if $slug is given, check if this is a Publisher feed slug
		return in_array($slug, $feed_slugs);
	}
}
