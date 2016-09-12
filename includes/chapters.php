<?php
use Podlove\Model;

/**
 * Enable chapters pages
 *
 * add ?chapters_format=psc|json|mp4chaps to any episode URL to get chapters
 */
add_action( 'wp', function() {

	if ( ! is_single() )
		return;

	$chapters_format_regex = apply_filters( 'podlove_chapters_format_regex', "/^(psc|json|mp4chaps)$/" );
	$chapters_format = filter_input(INPUT_GET, 'chapters_format', FILTER_VALIDATE_REGEXP, [
		'options' => ['regexp' => $chapters_format_regex]
	]);

	if ( ! $chapters_format )
		return;

	if ( ! $episode = Model\Episode::find_one_by_post_id( get_the_ID() ) )
		return;

	switch ( $chapters_format ) {
		case 'psc':
			header( "Content-Type: application/xml" );
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			break;
		case 'mp4chaps':
			header( "Content-Type: text/plain" );
			break;
		case 'json':
			header( "Content-Type: application/json" );
			break;
	}

	do_action( 'podlove_chapter_page', $chapters_format, $episode );

	echo $episode->get_chapters( $chapters_format );

	exit;
} );

// extend episode form
add_filter('podlove_episode_form_data', function($form_data, $episode) {

	$form_data[] = array(
		'type' => 'text',
		'key'  => 'chapters',
		'options' => array(
			'label'       => __( 'Chapter Marks', 'podlove-podcasting-plugin-for-wordpress' ),
			'description' => __( 'One timepoint (hh:mm:ss[.mmm]) and the chapter title per line.', 'podlove-podcasting-plugin-for-wordpress' ),
			'html'        => array(
				'class'       => 'large-text code autogrow',
				'placeholder' => '00:00:00.000 Intro',
				'rows'        => max( 2, count( explode( "\n", $episode->chapters ) ) )
			)
		),
		'position' => 800
	);

	return $form_data;
}, 10, 2);

add_filter('podlove_episode_data_filter', function ($filter) {
	return array_merge($filter, [
		'chapters'  => FILTER_UNSAFE_RAW
	]);
});

// add PSC to rss feed
add_action('podlove_append_to_feed_entry', function($podcast, $episode, $feed, $format) {
	$chapters = new \Podlove\Feeds\Chapters($episode);
	$chapters->render('inline');
}, 10, 4);
