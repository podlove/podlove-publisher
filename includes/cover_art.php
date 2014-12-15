<?php
use Podlove\Model;

// extend episode form
add_filter('podlove_episode_form_data', function($form_data, $episode) {
	
	if ( Model\AssetAssignment::get_instance()->image !== 'manual' )
		return $form_data;

	$form_data[] = array(
		'type' => 'string',
		'key'  => 'cover_art',
		'options' => array(
			'label'       => __( 'Episode Cover Art URL', 'podlove' ),
			'description' => __( 'JPEG or PNG. At least 1400 x 1400 pixels.', 'podlove' ),
			'html'        => array( 'class' => 'regular-text podlove-check-input' )
		),
		'position' => 790
	);

	return $form_data;
}, 10, 2);