<?php

add_filter('podlove_episode_form_data', function ($form_data, $episode) {
	
	if (!\Podlove\get_setting( 'metadata', 'enable_episode_explicit' ) )
		return $form_data;

	$form_data[] = array(
		'type' => 'select',
		'key'  => 'explicit',
		'options' => array(
			'label'   => __( 'Explicit Content?', 'podlove' ),
			'type'    => 'checkbox',
			'html'    => array( 'style' => 'width: 200px;' ),
			'default' => '-1',
            'options' => array(0 => 'no', 1 => 'yes', 2 => 'clean')
		),
		'position' => 770
	);

	return $form_data;
});