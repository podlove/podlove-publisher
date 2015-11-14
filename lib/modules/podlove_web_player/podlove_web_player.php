<?php 
namespace Podlove\Modules\PodloveWebPlayer;

use Podlove\Model\Episode;

class Podlove_Web_Player extends \Podlove\Modules\Base {

	protected $module_name = 'Podlove Web Player';
	protected $module_description = 'An audio player for the web. Let users listen to your podcast right on your website';
	protected $module_group = 'web publishing';

	public function load() {
		if (\Podlove\get_webplayer_setting('version') === 'player_v3') {
			(new PlayerV3\Module)->load();
		} else {
			(new PlayerV2\Module)->load();
		}
	}

	public static function get_player_printer(Episode $episode) {
		if (\Podlove\get_webplayer_setting('version') === 'player_v3') {
			$printer = new PlayerV3\Html5Printer($episode);
			$printer->setAttributes(['data-podlove-web-player-source' => add_query_arg(['podloveEmbed' => true], get_permalink($episode->post_id))]);
			return $printer;
		} else {
			return new PlayerV2\Printer($episode);
		}
	}
}

