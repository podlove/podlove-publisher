<?php 
namespace Podlove\Modules\PodloveWebPlayer\Podigee;

use Podlove\Model\Episode;

class Module {
	
	public function load() {
		add_shortcode('podlove-episode-web-player', [__CLASS__, 'shortcode']);
	}

	public static function shortcode() {

		if (is_feed())
			return '';

		$episode = Episode::find_or_create_by_post_id(get_the_ID());
		$printer = new Html5Printer($episode);

		return $printer->render(null);
	}
}
