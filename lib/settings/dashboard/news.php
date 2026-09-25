<?php

namespace Podlove\Settings\Dashboard;

class News
{
    // FIXME: https://podlove.org/feed/ is currently broken
    public static function content()
    {
        $feeds = [
            'podlove' => [
                'link' => 'https://podlove.org/',
                'url' => 'https://podlove.org/feed/',
                'title' => __('Podlove News', 'podlove-podcasting-plugin-for-wordpress'),
                'items' => 5,
                'show_summary' => 1,
                'show_author' => 0,
                'show_date' => 1,
            ],
        ];

        \Podlove\load_template('settings/dashboard/news', ['feeds' => $feeds]);
    }
}
