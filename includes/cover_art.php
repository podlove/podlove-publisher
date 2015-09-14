<?php
use Podlove\Model;

// extend episode form
add_filter('podlove_episode_form_data', function($form_data, $episode) {
	
	if ( Model\AssetAssignment::get_instance()->image !== 'manual' )
		return $form_data;

	$form_data[] = array(
		'type' => 'upload',
		'key'  => 'cover_art',
		'options' => array(
			'label'       => __( 'Episode Cover Art URL', 'podlove' ),
			'description' => __( 'Enter URL or select image from media library. JPEG or PNG. At least 1400 x 1400 pixels.', 'podlove' ),
			'html'        => array( 'class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'url' )
		),
		'position' => 790
	);

	return $form_data;
}, 10, 2);

add_filter('podlove_episode_data_filter', function ($filter) {
	return array_merge($filter, [
		'cover_art' => FILTER_SANITIZE_URL
	]);
});