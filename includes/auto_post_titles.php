<?php 

use Podlove\Model\Podcast;

use Podlove\Model\Episode;

add_filter('the_title', 'podlove_maybe_override_post_titles', 10, 2);

function podlove_maybe_override_post_titles($original_title, $post_id) 
{
	if (get_post_type($post_id) !== 'podcast')
		return;

	$enable_generated_blog_post_title = \Podlove\get_setting( 'website', 'enable_generated_blog_post_title' );

	if (!$enable_generated_blog_post_title)
		return;

	$episode_title = podlove_generated_post_title($post_id);

	if ($episode_title) {
		return $episode_title;
	} else {
		return $original_title;
	}
}

function podlove_generated_post_title($post_id)
{
	$blog_title_template = \Podlove\get_setting( 'website', 'blog_title_template' );
	$episode = Episode::find_one_by_post_id($post_id);

	if (!$blog_title_template || !$episode)
		return false;

	$title = $blog_title_template;
	$title = str_replace('%mnemonic%', strip_tags(podlove_get_mnemonic($post_id)), $title);
	$title = str_replace('%episode_number%', str_pad((string) $episode->number, 3, '0', STR_PAD_LEFT), $title);
	$title = str_replace('%episode_title%', trim(strip_tags($episode->title)), $title);

	return trim($title);
}

function podlove_get_mnemonic($post_id = null)
{
	$podcast = Podcast::get();
	return $podcast->mnemonic; // fimxe: handle mnemonic overrides via seasons
}
