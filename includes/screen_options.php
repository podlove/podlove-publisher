<?php 

function podlove_episodes_per_page_option_name() {
	return 'podlove_episodes_per_page';
}

add_filter("set-screen-option", function($status, $option, $value) {
	if ($option == podlove_episodes_per_page_option_name())
		return $value;
	
	return $status;
}, 10, 3);

add_action('admin_menu', function() {
	add_action('load-' . \Podlove\Settings\Analytics::$pagehook, function() {
		add_screen_option('per_page', [
			'label'   => __('Episodes per page', 'podlove-podcasting-plugin-for-wordpress'),
			'default' => 10,
			'option'  => podlove_episodes_per_page_option_name()
		]);
	});
}, 20);
