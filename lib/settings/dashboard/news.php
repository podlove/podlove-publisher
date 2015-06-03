<?php
namespace Podlove\Settings\Dashboard;

class News {

	public static function content() {
		$feeds = [
			'podlove' => [
				'link'         => 'http://podlove.org/',
				'url'          => 'http://podlove.org/feed/',
				'title'        => 'Podlove News',
				'items'        => 5,
				'show_summary' => 1,
				'show_author'  => 0,
				'show_date'    => 1,
			]
		];

		\Podlove\load_template('settings/dashboard/news', ['feeds' => $feeds]);
	}
}