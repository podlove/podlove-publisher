<?php 
namespace Podlove\Modules\PodloveWebPlayer;

use Podlove\Model\Episode;

class Podlove_Web_Player extends \Podlove\Modules\Base {

	protected $module_name = 'Podlove Web Player';
	protected $module_description = 'An audio player for the web. Let users listen to your podcast right on your website';
	protected $module_group = 'web publishing';

	public function load() {

		switch (\Podlove\get_webplayer_setting('version')) {
			case 'player_v4':
				(new PlayerV4\Module)->load();
				break;
			case 'player_v3':
				(new PlayerV3\Module)->load();
				break;
			case 'player_v2':
				(new PlayerV2\Module)->load();
				break;
			case 'podigee':
				(new Podigee\Module)->load();
				break;
		}

		$this->register_option( 'use_cdn', 'radio', [
			'label' => __( 'Use CDN?', 'podlove-podcasting-plugin-for-wordpress' ),
			'description' => '<p>' . __( 'Use our CDN (https://cdn.podlove.org) to always have the current version of the player on your site. Alternatively deliver the player with your own WordPress instance with the disadvantage of not using the most recent version all the time. This setting only applies to Podlove Web Player 4.', 'podlove-podcasting-plugin-for-wordpress' ) . '</p>',
			'default' => '1',
			'options' => [
				1 => __('yes, use CDN', 'podlove-podcasting-plugin-for-wordpress') . ' (' . __('recommended', 'podlove-podcasting-plugin-for-wordpress') .  ')',
				0 => __('no, deliver with WordPress', 'podlove-podcasting-plugin-for-wordpress')
			]
		]);		

		// this must _always_ be on, otherwise embedded players on other sites will stop working
		Podigee\Module::register_config_url_route();
		PlayerV4\Module::register_config_url_route();
	}

	public static function get_player_printer(Episode $episode) {

		switch (\Podlove\get_webplayer_setting('version')) {
			case 'player_v4':
				$printer = new PlayerV4\Html5Printer($episode);
				return $printer;
				break;
			case 'player_v3':
				$printer = new PlayerV3\Html5Printer($episode);
				$printer->setAttributes([
					'data-podlove-web-player-source' => add_query_arg(['podloveEmbed' => true], get_permalink($episode->post_id))
				]);
				return $printer;
				break;
			case 'player_v2':
				return new PlayerV2\Printer($episode);
				break;
			case 'podigee':
				return new Podigee\Html5Printer($episode);
				break;
		}

	}
}

