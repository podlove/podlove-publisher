<?php
return [
	'podlove_help_shows' => [
		'title'   => __('Shows', 'podlove-podcasting-plugin-for-wordpress'),
		'content' => 
			'<p>
				'.__(
					'Please find information on Podcast Networks in the <a href="http://docs.podlove.org/podlove-publisher/guides/podcast-network.html">Podlove Publisher Documentation</a>.', // @todo: Add a good description on the differences between shows and networks.
					'podlove-podcasting-plugin-for-wordpress'
				).'
			</p>'
	],
	'podlove_help_shows_slug' => [
		'title'   => __('Show Slug', 'podlove-podcasting-plugin-for-wordpress'),
		'content' => 
			'<p>
				'.__('The slug is used to create unique feeds for each show. Please consider the URL preview to see how the slug is used to create show-specific feeds. An overview over all show specific feeds is available on the <a href="?page=podlove_shows_settings">Show landing page</a>.', 'podlove-podcasting-plugin-for-wordpress').'
			</p>'
	]
];