<?php
// 'podlove_unique_tab_id' => [
// 	'title'   => __('Tab Title', 'podlove-podcasting-plugin-for-wordpress'),
// 	'content' => 
// 		'<p>'
// 			. __('Tab Content', 'podlove-podcasting-plugin-for-wordpress')
// 		. '</p>'
// ]
return [
	'podlove_analytics_intro' => [
		'title'   => __('Download Analytics', 'podlove-podcasting-plugin-for-wordpress'),
		'content' => 
			'<p>'
			. __('Podlove Publisher tracks <em>Download Intents</em>: the start of a download by a client. Those numbers do not represent if a download was completed or listened to.', 'podlove-podcasting-plugin-for-wordpress')
			. '</p>'
			. '<p>'
			. sprintf(
				__('For details on what is tracked and how, please visit: %s.', 'podlove-podcasting-plugin-for-wordpress'),
				'<a href="http://docs.podlove.org/podlove-publisher/guides/download-analytics.html" target="_blank">' . __('Download Analytics Guide', 'podlove-podcasting-plugin-for-wordpress') . '</a>'
			)
			. '</p>'
	],
	'podlove_analytics_time' => [
		'title'   => __('Absolute &amp; Relative Time', 'podlove-podcasting-plugin-for-wordpress'),
		'content' => 
			'<p>'
			. __('In most statistics we use the episode release time as a starting point for calculations. Which means "the first day" is not a calendar-day but the first 24 hours after an episode was released. This enables meaningful comparisons between episodes.', 'podlove-podcasting-plugin-for-wordpress')
			. '</p>'
			. '<p>'
			. __('However, when multiple episodes are plotted in the same chart with a time-axis, this is not possible (see downloads chart in the Analytics dashboard). Then absolute times are used.', 'podlove-podcasting-plugin-for-wordpress')
			. '</p>'
			. '<p>'
			. __('Keep that in mind when comparing numbers between different statistics.', 'podlove-podcasting-plugin-for-wordpress')
			. '</p>'
	],
	'podlove_analytics_columns' => [
		'title'   => __('Column Names', 'podlove-podcasting-plugin-for-wordpress'),
		'content' => 
			'<p>'
			. '<ul>'
			. '<li><strong>' . __('1d', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First day (24 hours) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('2d', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 2 days (48 hours) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('3d', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 3 days (72 hours) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('4d', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 4 days (96 hours) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('5d', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 5 days (120 hours) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('6d', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 6 days (144 hours) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('1w', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 7 days (168 hours) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('2w', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 2 weeks (336 hours) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('3w', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 3 weeks (504 hours) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('4w', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 4 weeks (672 hours) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('1q', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First quarter (13 weeks) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('2q', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 2 quarters (26 weeks) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('3q', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 3 quarters (39 weeks) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('1y', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First year (52 weeks) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('2y', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 2 years (104 weeks) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '<li><strong>' . __('3y', 'podlove-podcasting-plugin-for-wordpress') . ':</strong> ' . __('First 3 years (156 weeks) after episode release', 'podlove-podcasting-plugin-for-wordpress') . '</li>'
			. '</ul>'
			. '</p>'
	],
];
