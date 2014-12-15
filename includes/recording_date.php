<?php

add_filter('podlove_episode_form_data', function ($form_data) {
	
	if (!\Podlove\get_setting('metadata', 'enable_episode_recording_date'))
		return $form_data;

	$form_data[] = array(
		'type' => 'string',
		'key'  => 'recording_date',
		'options' => array(
			'label'       => __( 'Recording Date', 'podlove' ),
			'description' => '',
			'html'        => array( 'class' => 'regular-text podlove-check-input' )
		),
		'position' => 750
	);

	return $form_data;
});