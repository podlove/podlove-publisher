<?php
namespace Podlove\AJAX;

use \Podlove\Model;

class Ajax {

	/**
	 * Conventions: 
	 * - all actions must be prefixed with "podlove-"
	 * - hyphens in actions are substituted for underscores in methods
	 */
	public function __construct() {

		// workaround to make is_network_admin() work in ajax requests
		// @see https://core.trac.wordpress.org/ticket/22589
		if (!defined('WP_NETWORK_ADMIN') && defined('DOING_AJAX') && DOING_AJAX && is_multisite() && preg_match('#^' . network_admin_url() . '#i', $_SERVER['HTTP_REFERER'])) {
			define('WP_NETWORK_ADMIN',true);
		}

		$actions = array(
			'get-new-guid',
			'validate-url',
			'update-asset-position',
			'update-feed-position',
			'podcast',
			'hide-teaser',
			'get-license-url',
			'get-license-name',
			'get-license-parameters-from-url',
			'analytics-downloads-per-day',
			'analytics-episode-downloads-per-hour',
			'analytics-total-downloads-per-day',
			'analytics-episode-average-downloads-per-hour',
			'analytics-settings-tiles-update',
			'episode-slug',
			'admin-news',
			'job-create',
			'job-get'
		);

		// kickoff generic ajax methods
		foreach ( $actions as $action )
			add_action( 'wp_ajax_podlove-' . $action, array( $this, str_replace( '-', '_', $action ) ) );

		// kickof specialized ajax controllers
		TemplateController::init();
		FileController::init();

	}

	public function job_create() {
		$job_name = filter_input(INPUT_POST, 'name');
		$job_args = isset($_REQUEST['args']) && is_array($_REQUEST['args']) ? $_REQUEST['args'] : [];

		// check class exists
		if (!class_exists($job_name))
			self::respond_with_json(['error' => 'job "' . $job_name . '" does not exist']);

		// check that class is a job
		if (!isset(class_uses($job_name)['Podlove\Jobs\JobTrait'])) {
			self::respond_with_json(['error' => '"' . $job_name . '" is not a job']);
		}

		$job = \Podlove\Jobs\CronJobRunner::create_job($job_name, $job_args);

		if ($job) {
			self::respond_with_json([
				'job_id' => $job->get_job_id()
			]);
		} else {
			self::respond_with_json(['error' => 'A job "' . $job_name . '" is already running']);
		}
	}

	public function job_get() {
		$job_id = filter_input(INPUT_GET, 'job_id');

		$job = \Podlove\Model\Job::find_by_id($job_id);

		if (!$job)
			self::respond_with_json(['error' => 'no job with id "' . $job_id . '"']);

		self::respond_with_json($job->to_array());
	}

	public function admin_news() {
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		\Podlove\Settings\Dashboard\News::content();
		wp_die();
	}

	public function analytics_episode_average_downloads_per_hour()
	{
		global $wpdb;

		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		$downloads = $wpdb->get_col("
			SELECT
				meta_value
			FROM
				$wpdb->postmeta pm
				JOIN $wpdb->posts p ON pm.post_id = p.ID
			WHERE
				pm.meta_key = '_podlove_eda_downloads'
				AND p.post_status IN ('publish', 'private')
			GROUP BY
				pm.post_id
		");

		$downloads = array_reduce($downloads, function($agg, $item) {

			$row = explode(",", $item);

			// skip episodes with missing data, for example if released before tracking was started
			if (array_sum(array_slice($row, 0, 24)) < 10) {
				return $agg;
			}

			// skip young episodes
			if (count($row) < \Podlove\Analytics\EpisodeDownloadAverage::HOURS_TO_CALCULATE/2)
				return $agg;

			if (empty($agg)) {
				$agg = $row;
			} else {
				for ($i=0; $i < \Podlove\Analytics\EpisodeDownloadAverage::HOURS_TO_CALCULATE; $i++) { 
					if (isset($row[$i])) {
						$agg['downloads'][$i] += $row[$i];
					}
				}
				$agg['rows']++;
			}

			return $agg;
		}, array('downloads' => array_fill(0, \Podlove\Analytics\EpisodeDownloadAverage::HOURS_TO_CALCULATE, 0), 'rows' => 0));

		$downloads = array_map(function($item) use ($downloads) {
			return round($item / $downloads['rows']);
		}, $downloads['downloads']);

		$csv = '"downloads","hoursSinceRelease"' . "\n";
		foreach ($downloads as $key => $value) {
			$csv .= "$value,$key\n";
		}

		\Podlove\Feeds\check_for_and_do_compression('text/plain');
		echo $csv;
		ob_end_flush();
		exit;
	}

	public function analytics_downloads_per_day() {

		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		\Podlove\Feeds\check_for_and_do_compression('text/plain');

		$episode_id = isset($_GET['episode']) ? (int) $_GET['episode'] : 0;

		$cache = \Podlove\Cache\TemplateCache::get_instance();
		echo $cache->cache_for('podlove_analytics_dpd_' . $episode_id, function() use ($episode_id) {
			global $wpdb;

			$episode_cond = "";
			if ($episode_id) {
				$episode_cond = " AND episode_id = $episode_id";
			}

			$sql = "SELECT COUNT(*) downloads, post_title, access_date, episode_id, post_id
					FROM (
						SELECT
							media_file_id, accessed_at, DATE(accessed_at) access_date, episode_id
						FROM
							" . Model\DownloadIntent::table_name() . " di 
							INNER JOIN " . Model\MediaFile::table_name() . " mf ON mf.id = di.media_file_id
						WHERE 1 = 1 $episode_cond
						GROUP BY media_file_id, request_id, access_date
					) di
                    INNER JOIN " . Model\Episode::table_name() . " e ON episode_id = e.id
					INNER JOIN $wpdb->posts p ON e.post_id = p.ID
					WHERE accessed_at > p.post_date_gmt
					GROUP BY access_date, episode_id";

			$results = $wpdb->get_results($sql, ARRAY_N);

			$release_date = min(array_column($results, 2));

			$csv = '"downloads","title","date","episode_id","post_id","days"' . "\n";
			foreach ($results as $row) {
				$row[1] = '"' . str_replace('"', '""', $row[1]) . '"'; // quote & escape title
				$row[] = date_diff(date_create($release_date), date_create($row[2]))->format('%a');
				$csv .= implode(",", $row) . "\n";
			}

			return $csv;
		}, 3600);

		exit;
	}

	public function analytics_episode_downloads_per_hour() {

		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		$episode_id = isset($_GET['episode']) ? (int) $_GET['episode'] : 0;
		$cache_key = 'podlove_analytics_dphx_' . $episode_id;

		$cache = \Podlove\Cache\TemplateCache::get_instance();
		$content = $cache->cache_for($cache_key, function() use ($episode_id) {
			global $wpdb;

			$sql = "SELECT
						COUNT(*) downloads,
						UNIX_TIMESTAMP(accessed_at) AS access_date,
						hours_since_release,
						mf.episode_asset_id asset_id,
						client_name,
						os_name AS system,
						source,
						context
					FROM
						" . Model\DownloadIntentClean::table_name() . " di
						INNER JOIN " . Model\MediaFile::table_name() . " mf ON mf.id = di.media_file_id
						LEFT JOIN " . Model\UserAgent::table_name() . " ua ON ua.id = di.user_agent_id
						WHERE episode_id = $episode_id
						GROUP BY hours_since_release, asset_id, client_name, system, source, context";

			$results = $wpdb->get_results($sql, ARRAY_N);

			$csv = '"downloads","date","hours_since_release","asset_id","client","system","source","context"' . "\n";
			foreach ($results as $row) {
				$row[4] = '"' . $row[4] . '"';
				$row[5] = '"' . $row[5] . '"';
				$csv .= implode(",", $row) . "\n";
			}

			return $csv;
		}, 3600);

		$etag = md5($content);

		header("Etag: $etag");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", $cache->expiration_for($cache_key)) . " GMT");

		$etagHeader = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

		if ($etagHeader == $etag) {
			header("HTTP/1.1 304 Not Modified");
			exit;
		}

		\Podlove\Feeds\check_for_and_do_compression('text/plain');
		echo $content;
		ob_end_flush();
		exit;
	}

	public function analytics_total_downloads_per_day() {

		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		$cache_key = 'podlove_analytics_tdphx';

		$cache = \Podlove\Cache\TemplateCache::get_instance();
		$content = $cache->cache_for($cache_key, function() {
			global $wpdb;

			$sql = "SELECT
			    COUNT(*) downloads,
			    UNIX_TIMESTAMP(accessed_at) AS access_date,
			    DATE_FORMAT(accessed_at, '%Y-%m-%d') AS date_day,
			    mf.episode_id
			FROM
			    " . Model\DownloadIntentClean::table_name() . "  di
			    INNER JOIN " . Model\MediaFile::table_name() . " mf ON mf.id = di.media_file_id
			WHERE accessed_at >= STR_TO_DATE('" . date("Y-m-d", strtotime("-28 days")) . "','%Y-%m-%d')
			GROUP BY date_day, episode_id
			";

			$results = $wpdb->get_results($sql, ARRAY_N);

			$csv = '"downloads","date","day","episode_id"' . "\n";
			foreach ($results as $row) {
				$csv .= implode(",", $row) . "\n";
			}

			return $csv;
		}, 3600);

		$etag = md5($content);

		header("Etag: $etag");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", $cache->expiration_for($cache_key)) . " GMT");

		$etagHeader = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

		if ($etagHeader == $etag) {
			header("HTTP/1.1 304 Not Modified");
			exit;
		}

		\Podlove\Feeds\check_for_and_do_compression('text/plain');
		echo $content;
		ob_end_flush();
		exit;
	}

	public static function analytics_settings_tiles_update() {

		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		$tile_id = $_GET['tile_id'];
		$checked = isset($_GET['checked']) && $_GET['checked'] === 'checked';

		$option = get_option('podlove_analytics_tiles', array());
		$option[$tile_id] = $checked;
		update_option('podlove_analytics_tiles', $option);
	}

	public static function respond_with_json( $result ) {
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
		header( 'Content-type: application/json' );
		echo json_encode( $result );
		die();
	}

	public function podcast() {
		$podcast = Model\Podcast::get();
		$podcast_data = array();
		foreach ( $podcast->property_names() as $property ) {
			$podcast_data[ $property ] = $podcast->$property;
		}
		
		self::respond_with_json( $podcast_data );
	}

	public function get_new_guid() {
		$post_id = $_REQUEST['post_id'];

		$post = get_post( $post_id );
		$guid = \Podlove\Custom_Guid::guid_for_post( $post );

		self::respond_with_json( array( 'guid' => $guid ) );
	}

	public function validate_url() {

		if ( ! current_user_can( 'administrator' ) ) {
			echo 'No permission';
			exit;
		}

		$file_url = $_REQUEST['file_url'];

		$info = \Podlove\Model\MediaFile::curl_get_header_for_url( $file_url );
		$header = $info['header'];
		$reachable = $header['http_code'] >= 200 && $header['http_code'] < 300;

		$validation_cache = get_option( 'podlove_migration_validation_cache', array() );
		$validation_cache[ $file_url ] = $reachable;
		update_option( 'podlove_migration_validation_cache', $validation_cache );

		self::respond_with_json( array(
			'file_url'	=> $file_url,
			'reachable'	=> $reachable,
			'file_size'	=> $header['download_content_length']
		) );
	}

	public function update_asset_position() {

		if ( ! current_user_can( 'administrator' ) ) {
			echo 'No permission';
			exit;
		}

		$asset_id = (int)   $_REQUEST['asset_id'];
		$position = (float) $_REQUEST['position'];

		Model\EpisodeAsset::find_by_id( $asset_id )
			->update_attributes( array( 'position' => $position ) );

		die();
	}

	public function update_feed_position() {

		if ( ! current_user_can( 'administrator' ) ) {
			echo 'No permission';
			exit;
		}

		$feed_id = (int)   $_REQUEST['feed_id'];
		$position = (float) $_REQUEST['position'];

		Model\Feed::find_by_id( $feed_id )
			->update_attributes( array( 'position' => $position ) );

		die();
	}

	public function hide_teaser() {
		update_option( '_podlove_hide_teaser', TRUE );
	}

	private function parse_get_parameter_into_url_array() {
		return array(
						'version'		 => $_REQUEST['version'],
						'modification'	 => $_REQUEST['modification'],
						'commercial_use' => $_REQUEST['commercial_use'],
						'jurisdiction'	 => $_REQUEST['jurisdiction']
					);
	}

	public function get_license_url() {
		self::respond_with_json( \Podlove\Model\License::get_url_from_license( self::parse_get_parameter_into_url_array() ) );
	}

	public function get_license_name() {
		self::respond_with_json( \Podlove\Model\License::get_name_from_license( self::parse_get_parameter_into_url_array() ) );
	}

	public function get_license_parameters_from_url() {
		self::respond_with_json( \Podlove\Model\License::get_license_from_url( $_REQUEST['url'] ) );
	}

	public function episode_slug() {
		echo sanitize_title($_REQUEST['title']);
		die();
	}	
}
