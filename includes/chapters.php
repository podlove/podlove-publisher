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

	$chapters_format = filter_input(INPUT_GET, 'chapters_format', FILTER_VALIDATE_REGEXP, [
		'options' => ['regexp' => "/^(psc|json|mp4chaps)$/"]
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
	
	echo $episode->get_chapters( $chapters_format );
	exit;
} );

/**
 * When changing from an external chapter asset to 'manual', copy external 
 * contents into local field.
 */
add_filter('pre_update_option_podlove_asset_assignment', function($new, $old) {
	global $wpdb;

	if (!isset($old['chapters']) || !isset($new['chapters']))
		return $new;

	if ($new['chapters'] != 'manual')  // just changes to manual
		return $new;

	if (((int) $old['chapters']) <= 0) // just changes from an asset
		return $new;

	$episodes = \Podlove\Model\Episode::find_all_by_time();

	// 10 seconds per episode or 30 seconds since 1 request per asset 
	// is required if it is not cached
	set_time_limit(max(30, count($episodes) * 10));

	foreach ($episodes as $episode) {
		if ($chapters = $episode->get_chapters('mp4chaps'))
			$episode->update_attribute('chapters', esc_sql($chapters));
	}

	// delete chapters caches
	$wpdb->query('DELETE FROM `' . $wpdb->options . '` WHERE option_name LIKE "%podlove_chapters_string_%"');

	return $new;
}, 10, 2);

// extend episode form
add_filter('podlove_episode_form_data', function($form_data, $episode) {
	
	if ( Model\AssetAssignment::get_instance()->chapters !== 'manual' )
		return $form_data;

	$form_data[] = array(
		'type' => 'text',
		'key'  => 'chapters',
		'options' => array(
			'label'       => __( 'Chapter Marks', 'podlove' ),
			'description' => __( 'One timepoint (hh:mm:ss[.mmm]) and the chapter title per line.', 'podlove' ),
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
