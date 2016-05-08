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

	public static function register_config_url_route() {
		add_action('init', [__CLASS__, 'config_url_route']);
	}

	public static function config_url_route() {

		if (!isset($_GET['podigee_player']))
			return;

		$episode_id = (int) $_GET['podigee_player'];

		if (!$episode_id)
			return;

		$episode = Episode::find_by_id($episode_id);

		if (!$episode)
			return;

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
