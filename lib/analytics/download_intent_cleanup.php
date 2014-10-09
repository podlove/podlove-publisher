<?php
namespace Podlove\Analytics;

use Podlove\Model;

/**
 * Cron manager to fill DownloadIntentClean table
 */
class DownloadIntentCleanup {

	public static function init()
	{
		self::schedule_crons();

		add_action('podlove_cleanup_download_intents', array(__CLASS__, 'cleanup_download_intents'));
	}

	public static function schedule_crons() {
		if (!wp_next_scheduled('podlove_cleanup_download_intents'))
			wp_schedule_event(time(), 'hourly', 'podlove_cleanup_download_intents');
	}

	public static function cleanup_download_intents() {
		global $wpdb;

		$sql = "INSERT INTO `" . Model\DownloadIntentClean::table_name() . "` (`id`, `user_agent_id`, `media_file_id`, `request_id`, `accessed_at`, `source`, `context`, `geo_area_id`, `lat`, `lng`, `httprange`)
		SELECT
			di.id, `user_agent_id`, `media_file_id`, `request_id`, `accessed_at`, `source`, `context`, `geo_area_id`, `lat`, `lng`, `httprange`
		FROM
			`" . Model\DownloadIntent::table_name() . "` di
			INNER JOIN " . Model\MediaFile::table_name() . " mf ON mf.id = di.media_file_id -- filter dead intents
			INNER JOIN " . Model\Episode::table_name() . " e ON episode_id = e.id
			INNER JOIN $wpdb->posts p ON e.post_id = p.ID
		WHERE
			di.accessed_at > p.post_date_gmt -- ignore pre-release intents
			AND user_agent_id NOT IN (SELECT id FROM `" . Model\UserAgent::table_name() . "` WHERE bot) -- filter out bots
			AND di.id > %d
		GROUP BY media_file_id, request_id, DATE_FORMAT(accessed_at, '%%Y-%%m-%%d %%H') -- deduplication
		";

		$wpdb->query(
			$wpdb->prepare($sql, self::get_last_insert_id())
		);
	}

	public static function get_last_insert_id() {
		global $wpdb;
		return $wpdb->get_var("SELECT MAX(id) FROM " . Model\DownloadIntentClean::table_name());
	}
}