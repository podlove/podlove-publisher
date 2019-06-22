<?php
return [
    'podlove_help_shows'      => [
        'title'   => __('Shows', 'podlove-podcasting-plugin-for-wordpress'),
        'content' =>
        '<p>'
        . sprintf(
            __(
                'Use shows to offer feeds to subtopics of your podcast. If your shows are unrelated, a WordPress Network is better suited than the shows module. Have a look at %sthe documentation%s for a detailed overview.', // @todo: Add a good description on the differences between shows and networks.
                'podlove-podcasting-plugin-for-wordpress'
            ),
            '<a href="http://docs.podlove.org/podlove-publisher/guides/podcast-network.html" target="_blank">',
            '</a>'
        ) . '</p>',
    ],
    'podlove_help_shows_slug' => [
        'title'   => __('Show Slug', 'podlove-podcasting-plugin-for-wordpress'),
        'content' =>
        '<p>
				' . __('The slug is used to create unique feeds for each show. Please consider the URL preview to see how the slug is used to create show-specific feeds. An overview over all show specific feeds is available on the <a href="?page=podlove_shows_settings">Show landing page</a>.', 'podlove-podcasting-plugin-for-wordpress') . '
			</p>',
    ],
];
