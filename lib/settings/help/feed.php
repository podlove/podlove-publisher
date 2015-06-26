<?php
// 'podlove_unique_tab_id' => [
// 	'title'   => __('Tab Title', 'podlove'),
// 	'content' => 
// 		'<p>'
// 			. __('Tab Content', 'podlove')
// 		. '</p>'
// ]
return [
	'podlove_help_feed_slug' => [
		'title'   => __('Feed Slugs', 'podlove'),
		'content' => 
			'<p>'
				. __('Every feed URL is unique. To make it unique, you must assign each feed a unique <em>slug</em>.
					It\'s a good habit to use your asset:', 'podlove')
				. '<ul>'
					. '<li>' . __('"mp3" slug for your mp3 asset', 'podlove') . '</li>'
					. '<li>' . __('"m4a" slug for your m4a asset', 'podlove') . '</li>'
					. '<li>' . __('etc.', 'podlove') . '</li>'
				. '</ul>'
			. '</p>'
	],
	'podlove_help_feed_asset' => [
		'title'   => __('Feed Assets', 'podlove'),
		'content' =>
			'<p>'
				. __('Each feed contains exactly one asset. You should have one feed for each asset you want your users to be able to subscribe to.', 'podlove')
			. '</p>'
	]
];
