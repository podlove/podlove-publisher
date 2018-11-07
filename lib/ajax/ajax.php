<?php
namespace Podlove\AJAX;

use \Podlove\Model;
use League\Csv\Writer;

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
			'analytics-settings-avg-update',
			'analytics-global-assets',
			'analytics-global-clients',
			'analytics-global-systems',
			'analytics-global-sources',
			'analytics-global-downloads-per-month',
			'analytics-global-top-episodes',
			'analytics-csv-episodes-table',
			'episode-slug',
			'admin-news',
			'job-create',
			'job-get',
			'job-delete',
			'jobs-get'
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

	public function job_delete() {
		$job_id = filter_input(INPUT_GET, 'job_id');
		$job = \Podlove\Model\Job::find_by_id($job_id);

		if (!$job)
			self::respond_with_json(['error' => 'no job with id "' . $job_id . '"']);

		$job->delete();

		self::respond_with_json(["status" => "ok"]);
	}

	public function jobs_get() {
		$jobs = \Podlove\Model\Job::all();
		$jobs = array_map(function($j) {
			$job = $j->to_array();

			$job_class = $job['class'];

			if (!class_exists($job_class)) {
				$job_class = str_replace("\\\\", "\\", $job_class);
			}

			$job['title'] = $job_class::title();
			// $job['description'] = $job_class::description();
			$job['mode'] = $job_class::mode(maybe_unserialize($job['args']));

			if ($job['steps_total'] > 0) {
				$steps_percent = floor(100 * ($job['steps_progress'] / $job['steps_total']));
				$job['steps_percent'] = $steps_percent < 100 ? $steps_percent : 100;
			} else {
				$job['steps_percent'] = 0;
			}

			$job['active_run_time'] = round($job['active_run_time'], 2);

			$job['created_relative'] = sprintf(__('%s ago'), human_time_diff(strtotime($job['created_at'])));
			$job['created_at_timestamp'] = strtotime($job['created_at']);
			
			if (!$job['wakeups'] || $job['created_at'] == $job['updated_at']) {
				$job['last_progress'] = __('Never');
			} else {
				$seconds = time() - strtotime($job['updated_at']);
				if ($seconds < 5) {
					$job['last_progress'] = __('just now');
				} elseif ($seconds < 60) {
					$job['last_progress'] = sprintf(__('%s sec ago'), $seconds);
				} else {
					$job['last_progress'] = sprintf(__('%s ago'), human_time_diff(strtotime($job['updated_at'])));
				}
			}

			return $job;
		}, $jobs);

		self::respond_with_json($jobs);
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

		$locale = get_locale();
		$known_langs = ['de', 'en', 'es', 'fr', 'ja', 'pt-BR', 'ru', 'zh-CN'];

		$lang = "en";
		foreach ($known_langs as $l) {
			if (stristr($locale, $l) !== false) {
				$lang = $l;
				break;
			}
		}

		$cache = \Podlove\Cache\TemplateCache::get_instance();
		$content = $cache->cache_for($cache_key, function() use ($episode_id, $lang) {
			global $wpdb;

			$sql = "SELECT
						COUNT(*) downloads,
						UNIX_TIMESTAMP(accessed_at) AS access_date,
						hours_since_release,
						mf.episode_asset_id asset_id,
						client_name,
						os_name AS system,
						source,
						context,
						geo.type as t1,
						geoname.name as tn1,
						geo_p.type as t2,
						geoname_p.name as tn2,
						geo_pp.type as t3,
						geoname_pp.name as tn3
					FROM
						" . Model\DownloadIntentClean::table_name() . " di
						INNER JOIN " . Model\MediaFile::table_name() . " mf ON mf.id = di.media_file_id
						LEFT JOIN " . Model\UserAgent::table_name() . " ua ON ua.id = di.user_agent_id

						LEFT JOIN " . Model\GeoArea::table_name() . " geo ON geo.id = di.`geo_area_id`
						LEFT JOIN " . Model\GeoArea::table_name() . " geo_p ON geo_p.id = geo.parent_id
						LEFT JOIN " . Model\GeoArea::table_name() . " geo_pp ON geo_pp.id = geo_p.parent_id
						LEFT JOIN " . Model\GeoAreaName::table_name() . " geoname ON geoname.area_id = geo.`id` and geoname.language = \"$lang\"
						LEFT JOIN " . Model\GeoAreaName::table_name() . " geoname_p ON geoname_p.area_id = geo_p.`id` and geoname_p.language = \"$lang\"
						LEFT JOIN " . Model\GeoAreaName::table_name() . " geoname_pp ON geoname_pp.area_id = geo_pp.`id` and geoname_pp.language = \"$lang\"
		
						WHERE episode_id = $episode_id
						GROUP BY hours_since_release, asset_id, client_name, system, source, context";

			$results = $wpdb->get_results($sql, ARRAY_N);

			$csv = '"downloads","date","hours_since_release","asset_id","client","system","source","context","geo"' . "\n";
			foreach ($results as $row) {

				$geos = [
					['type' => $row[8], 'name' => $row[9]],
					['type' => $row[10], 'name' => $row[11]],
					['type' => $row[12], 'name' => $row[13]]
				];

				unset($row[8], $row[9], $row[10], $row[11], $row[12], $row[13]);

				$geo = array_filter($geos, function($g) {
					return $g['type'] == 'country';
				});
				
				if (count($geo)) {
					$row[8] = reset($geo)['name'];
				} else {
					$row[8] = "";
				}

				$row[4] = '"' . $row[4] . '"';
				$row[5] = '"' . $row[5] . '"';
				$row[8] = '"' . $row[8] . '"';
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

	public static function analytics_settings_avg_update() {

		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		$checked = isset($_GET['checked']) && $_GET['checked'] === 'checked';
		update_option('podlove_analytics_compare_avg', $checked);
	}

	public static function analytics_csv_episodes_table() {

		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

    $data = \Podlove\Downloads_List_Data::get_data('post_date', 'asc');

		$writer = Writer::createFromFileObject(new \SplTempFileObject()); //the CSV file will be created into a temporary File
		$writer->setEncodingFrom("utf-8");

		$headers = array_keys($data[0]);
		$writer->insertOne($headers);

		$writer->insertAll($data);

		\Podlove\Feeds\check_for_and_do_compression('text/csv');
		header("Content-Disposition: attachment; filename=podlove-episode-downloads.csv");
		echo $writer;
		ob_end_flush();
		exit;
	}

	public static function respond_with_json( $result ) {
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
		header( 'Content-type: application/json' );
		echo json_encode( $result );
		die();
	}

// SELECT
//     count(id) downloads,
//     source
// FROM
//     wp_podlove_downloadintentclean
// GROUP BY
//     source
// ORDER BY
//     downloads DESC;

// SELECT
//     count(id) downloads,
//     CONCAT(source, "/", context)
// FROM
//     wp_podlove_downloadintentclean
// GROUP BY
//     source,
//     context
// ORDER BY
//     downloads DESC;

// SELECT
//     count(di.id) downloads,
//     t.name
// FROM
//     wp_podlove_downloadintentclean di
//     JOIN `wp_podlove_mediafile` f ON f.id = di.`media_file_id`
//     JOIN `wp_podlove_episodeasset` a ON a.id = f.`episode_asset_id`
//     JOIN `wp_podlove_filetype` t ON t.id = a.`file_type_id`
// GROUP BY
//     t.id
// ORDER BY
//     downloads DESC;
    

	public static function analytics_global_assets()
	{
		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		\Podlove\Feeds\check_for_and_do_compression('text/plain');

		echo \Podlove\Cache\TemplateCache::get_instance()->cache_for('analytics_global_assets' . self::analytics_date_cache_key(), function() {
			global $wpdb;

			$downloads = $wpdb->get_results("
				SELECT
					count(di.id) downloads, t.name
				FROM
					" . Model\DownloadIntentClean::table_name() . " di
					JOIN `" . Model\MediaFile::table_name() . "` f ON f.id = di.`media_file_id`
					JOIN `" . Model\EpisodeAsset::table_name() . "` a ON a.id = f.`episode_asset_id`
					JOIN `" . Model\FileType::table_name() . "` t ON t.id = a.`file_type_id`
				WHERE 1 = 1 AND " . self::analytics_date_condition() . "
				GROUP BY
					t.id
				ORDER BY
					downloads DESC;
			", ARRAY_N);

			$csv = Writer::createFromFileObject(new \SplTempFileObject());
			$csv->insertOne(['downloads', 'asset']);
			$csv->insertAll($downloads);

			return (string) $csv;
		});

		ob_end_flush();
		exit;
	}

	public static function analytics_global_clients()
	{
		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		\Podlove\Feeds\check_for_and_do_compression('text/plain');

		echo \Podlove\Cache\TemplateCache::get_instance()->cache_for('analytics_global_clients' . self::analytics_date_cache_key(), function() {
			global $wpdb;

			$downloads = $wpdb->get_results("
				SELECT
						count(di.id) downloads,
						ua.client_name
				FROM
						" . Model\DownloadIntentClean::table_name() . " di
						JOIN `" . Model\UserAgent::table_name() . "` ua ON ua.id = di.`user_agent_id`
				WHERE 1 = 1 AND " . self::analytics_date_condition() . "
				GROUP BY
						ua.client_name
				ORDER BY
						downloads DESC;
			", ARRAY_N);

			$csv = Writer::createFromFileObject(new \SplTempFileObject());
			$csv->insertOne(['downloads', 'client_name']);
			$csv->insertAll($downloads);

			return (string) $csv;
		});

		ob_end_flush();
		exit;
	}

	public static function analytics_global_sources()
	{
		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		\Podlove\Feeds\check_for_and_do_compression('text/plain');

		echo \Podlove\Cache\TemplateCache::get_instance()->cache_for('analytics_global_sources' . self::analytics_date_cache_key(), function() {
			global $wpdb;

			$downloads = $wpdb->get_results("
				SELECT
						count(id) downloads,
						source
				FROM
						" . Model\DownloadIntentClean::table_name() . "
				WHERE source IN ('feed', 'webplayer', 'download', 'opengraph') AND " . self::analytics_date_condition() . "
				GROUP BY
						source
				ORDER BY
						downloads DESC
			", ARRAY_N);

			$csv = Writer::createFromFileObject(new \SplTempFileObject());
			$csv->insertOne(['downloads', 'source']);
			$csv->insertAll($downloads);

			return (string) $csv;
		});

		ob_end_flush();
		exit;
	}

	public static function analytics_global_systems()
	{
		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		\Podlove\Feeds\check_for_and_do_compression('text/plain');

		echo \Podlove\Cache\TemplateCache::get_instance()->cache_for('analytics_global_systems' . self::analytics_date_cache_key(), function() {
			global $wpdb;

			$downloads = $wpdb->get_results("

				SELECT
						count(di.id) downloads,
						ua.os_name
				FROM
						" . Model\DownloadIntentClean::table_name() . " di
						JOIN `" . Model\UserAgent::table_name() . "` ua ON ua.id = di.`user_agent_id`
				WHERE 1 = 1 AND " . self::analytics_date_condition() . "
				GROUP BY
						ua.`os_name`
				ORDER BY
						downloads DESC;
			", ARRAY_N);

			$csv = Writer::createFromFileObject(new \SplTempFileObject());
			$csv->insertOne(['downloads', 'os_name']);
			$csv->insertAll($downloads);

			return (string) $csv;
		});

		ob_end_flush();
		exit;
	}

	public static function analytics_global_downloads_per_month()
	{
		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		\Podlove\Feeds\check_for_and_do_compression('text/plain');

		echo \Podlove\Cache\TemplateCache::get_instance()->cache_for('analytics_global_downloads_per_month' . self::analytics_date_cache_key(), function() {
			global $wpdb;

			$downloads = $wpdb->get_results("
				SELECT
						count(id),
						DATE_format(accessed_at, '%Y %m') date_month
				FROM
						" . Model\DownloadIntentClean::table_name() . " di
				WHERE 1 = 1 AND " . self::analytics_date_condition() . "
				GROUP BY
						date_month
				ORDER BY
						date_month ASC
			", ARRAY_N);

			$csv = Writer::createFromFileObject(new \SplTempFileObject());
			$csv->insertOne(['downloads', 'date_month']);
			$csv->insertAll($downloads);
			return (string) $csv;
		});
		
		ob_end_flush();
		exit;
	}

	private static function analytics_date_condition() {
		$from = filter_input(INPUT_GET, 'date_from');
		$to = filter_input(INPUT_GET, 'date_to');

		if (!$from || !$to)
			return "1 = 1";

		$from = new \DateTime($from);
		$to = new \DateTime($to);

		if (!$from || !$to)
			return "1 = 1";

		return "(accessed_at >= \"{$from->format('Y-m-d H:i:s')}\" AND accessed_at <= \"{$to->format('Y-m-d H:i:s')}\")";
	}

  private static function analytics_date_cache_key() {
		$condition = self::analytics_date_condition();
		return sha1($condition);
	}

	public static function analytics_global_top_episodes()
	{
		global $wpdb;

		if ( ! current_user_can( 'podlove_read_analytics' ) ) {
			exit;
		}

		\Podlove\Feeds\check_for_and_do_compression('text/plain');
	
		echo \Podlove\Cache\TemplateCache::get_instance()->cache_for('analytics_global_top_episodes' . self::analytics_date_cache_key(), function() {
			global $wpdb;

			$sql = "
				SELECT
						count(di.id) downloads,
						e.post_id,
						p.post_title
				FROM
						" . Model\DownloadIntentClean::table_name() . " di
						INNER JOIN " . Model\MediaFile::table_name() . " mf ON mf.id = di.media_file_id
						INNER JOIN " . Model\Episode::table_name() . " e ON e.id = mf.`episode_id`
						INNER JOIN " . $wpdb->posts . " p ON p.`ID` = e.post_id
				WHERE 1 = 1 AND " . self::analytics_date_condition() . "
				GROUP BY p.id
				ORDER BY downloads DESC
				LIMIT 10		
			";

			$downloads = $wpdb->get_results($sql, ARRAY_N);

			$csv = Writer::createFromFileObject(new \SplTempFileObject());
			$csv->insertOne(['downloads', 'post_id', 'title']);
			$csv->insertAll($downloads);
			return (string) $csv;
	  });

		ob_end_flush();
		exit;
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
