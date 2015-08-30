<?php 
namespace Podlove\Modules\PodloveWebPlayer\PlayerV3;

use Podlove\Model;
use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Model\EpisodeAsset;
use Podlove\Model\MediaFile;

class Module {
	
	public function load() {
		add_action('wp', [$this, 'embed_player']);

		add_action('wp_enqueue_scripts', function() {
			wp_enqueue_script(
				'podlove-player-moderator-script',
				plugins_url('js/podlove-web-moderator.min.js', __FILE__),
				[], \Podlove\get_plugin_header('Version')
			);
		});

		add_action('wp_print_footer_scripts', function() {
			echo '<script>jQuery("audio").podlovewebplayer();</script>';
		});

		// backward compatible, but only load if no other plugin has registered this shortcode
		if (!shortcode_exists('podlove-web-player'))
			add_shortcode('podlove-web-player', [__CLASS__, 'shortcode']);

		add_shortcode('podlove-episode-web-player', [__CLASS__, 'shortcode']);
	}

	public function embed_player() {
		
		if (!filter_input(INPUT_GET, 'podloveEmbed'))
			return;

		if (!is_single())
			return;

		if (!$episode = Episode::find_or_create_by_post_id(get_the_ID()))
			return;

		$css_path = plugins_url('css', __FILE__);
		$js_path  = plugins_url('js', __FILE__);

		$player_config = (new PlayerConfig($episode))->get();

		\Podlove\load_template(
			'lib/modules/podlove_web_player/player_v3/views/embed_player', 
			compact('episode', 'css_path', 'js_path', 'player_config')
		);

		exit;
	}

	public static function shortcode() {

		if (is_feed())
			return '';

		$episode = Episode::find_or_create_by_post_id(get_the_ID());
		$printer = new Html5Printer($episode);
		$printer->setAttributes(['data-podlove-web-player-source' => add_query_arg(['podloveEmbed' => true], get_permalink())]);

		return $printer->render(null);
	}
}