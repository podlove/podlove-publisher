<?php
// 'podlove_unique_tab_id' => [
// 	'title'   => __('Tab Title', 'podlove-podcasting-plugin-for-wordpress'),
// 	'content' => 
// 		'<p>'
// 			. __('Tab Content', 'podlove-podcasting-plugin-for-wordpress')
// 		. '</p>'
// ]
return [
	'podlove_help_feed_slug' => [
		'title'   => __('Feed Slugs', 'podlove-podcasting-plugin-for-wordpress'),
		'content' => 
			'<p>'
				. __('Every feed URL is unique. To make it unique, you must assign each feed a unique <em>slug</em>.
					It\'s a good habit to use your asset:', 'podlove')
				. '<ul>'
					. '<li>' . __('"mp3" slug for your mp3 asset', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
					. '<li>' . __('"m4a" slug for your m4a asset', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
					. '<li>' . __('etc.', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
				. '</ul>'
			. '</p>'
	],
	'podlove_help_feed_asset' => [
		'title'   => __('Feed Assets', 'podlove-podcasting-plugin-for-wordpress'),
		'content' =>
			'<p>'
				. __('Each feed contains exactly one asset. You should have one feed for each asset you want your users to be able to subscribe to.', 'podlove-podcasting-plugin-for-wordpress')
			. '</p>'
	]
];
