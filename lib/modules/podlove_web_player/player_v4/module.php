<?php 
namespace Podlove\Modules\PodloveWebPlayer\PlayerV4;

// use Podlove\Model;
use Podlove\Model\Episode;
// use Podlove\Model\Podcast;
// use Podlove\Model\EpisodeAsset;
// use Podlove\Model\MediaFile;

class Module {
	
	public function load() {

		add_action('wp_enqueue_scripts', function() {
			wp_enqueue_script(
				'podlove-player4-embed',
				plugins_url('dist/embed.js', __FILE__),
				[], \Podlove\get_plugin_header('Version')
			);
		});

		// backward compatible, but only load if no other plugin has registered this shortcode
		if (!shortcode_exists('podlove-web-player'))
			add_shortcode('podlove-web-player', [__CLASS__, 'shortcode']);

		add_shortcode('podlove-episode-web-player', [__CLASS__, 'shortcode']);
	}

	public static function shortcode() {

		if (is_feed())
			return '';

		$episode = Episode::find_one_by_post_id(get_the_ID());
		$printer = new Html5Printer($episode);

		return $printer->render(null);
	}


	public static function register_config_url_route() {
		add_action('init', [__CLASS__, 'config_url_route']);
	}

	public static function config_url_route() {

		if (!isset($_GET['podlove_player4']))
			return;

		$episode_id = (int) $_GET['podlove_player4'];
error_log(print_r("id: " . $episode_id, true));
		if (!$episode_id)
			return;

		$episode = Episode::find_by_id($episode_id);

		if (!$episode)
			return;

		error_log(print_r($episode, true));

		// allow CORS
		
		// Allow from any origin
		if (isset($_SERVER['HTTP_ORIGIN'])) {
		    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
		    header('Access-Control-Allow-Credentials: true');
		    header('Access-Control-Max-Age: 86400');    // cache for 1 day
		}

		// Access-Control headers are received during OPTIONS requests
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

		    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
		        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

		    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
		        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

		    exit(0);
		}

		// other headers
		header( 'Content-type: application/json' );

		$config = Html5Printer::config($episode, "embed");
		echo json_encode($config);
		exit;
	}
}
