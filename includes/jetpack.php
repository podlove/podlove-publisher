<?php

// enable jetpack's publicize for our podcast post type
add_action('init', 'podlove_jetpack_enable_publicize');

function podlove_jetpack_enable_publicize() {
    add_post_type_support('podcast', 'publicize');
}

// remove jetpack rss icon from podcast feeds which conflicts with our own rss icon
add_action('template_redirect', 'podlove_jetpack_remove_rss_icon', 11);

function podlove_jetpack_remove_rss_icon() {

	if (!method_exists('Jetpack_Site_Icon', 'init'))
		return;

	if (!$feed_slug = get_query_var('feed'))
		return;

	if (!$feed = \Podlove\Model\Feed::find_one_by_slug($feed_slug))
		return;

	remove_action( 'rss2_head', [Jetpack_Site_Icon::init(), 'rss2_icon'] );
}