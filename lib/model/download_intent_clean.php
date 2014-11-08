<?php
namespace Podlove\Model;

/**
 * Contains cleaned up data of DownloadIntent table.
 */
class DownloadIntentClean extends Base {

	public static function top_episode_ids($start, $end = "now", $limit = 3) {
		global $wpdb;

		$sql = "
			SELECT
				episode_id, COUNT(*) downloads
			FROM
				" . self::table_name() . " di
				JOIN " . MediaFile::table_name() . " mf ON mf.id = di.media_file_id
				JOIN " . Episode::table_name() . " e ON e.id = mf.episode_id
			WHERE
				" . self::sql_condition_from_time_strings($start, $end) . "
			GROUP BY
				episode_id
			ORDER BY
				downloads DESC
			LIMIT
				0, %d
		";

		return $wpdb->get_col(
			$wpdb->prepare($sql, $limit)
		);
	}

	/**
	 * For an episode, get the day with the most downloads and the number of downloads.
	 * 
	 * @param  int $episode_id
	 * @return array with keys "downloads" and "theday"
	 */
	public function peak_download_by_episode_id($episode_id) {
		global $wpdb;

		$sql = "
			SELECT
				COUNT(*) downloads, DATE(accessed_at) theday
			FROM
				" . self::table_name() . " di
			WHERE
				media_file_id IN (
					SELECT id FROM " . MediaFile::table_name() . " WHERE episode_id = %d
				)
			GROUP BY theday
			ORDER BY downloads DESC
			LIMIT 0,1
		";

		return $wpdb->get_row(
			$wpdb->prepare($sql, $episode_id),
			ARRAY_A
		);
	}

	public static function total_by_episode_id($episode_id, $start = null, $end = null) {
		global $wpdb;

		$sql = "
			SELECT
				COUNT(*)
			FROM
				" . self::table_name() . " di
			WHERE
				media_file_id IN (
					SELECT id FROM " . MediaFile::table_name() . " WHERE episode_id = %d
				)
				AND " . self::sql_condition_from_time_strings($start, $end) . "
		";

		return $wpdb->get_var(
			$wpdb->prepare($sql, $episode_id)
		);
	}

	/**
	 * Generate WHERE clause to a certain time range or day.
	 *
	 * If $start and $end are given, they describe a time range.
	 * If only $start is given, only data from this day will be returned.
	 * If none are given, there is no time restriction. "1 = 1" will be returned instead.
	 * 
	 * @param  string $start      Timerange start in words, or null. Default: null.
	 * @param  string $end        Timerange end in words, or null. Default: null.
	 * @param  string $tableAlias DownloadIntent table alias. Default: "di".
	 * @return string
	 */
	private static function sql_condition_from_time_strings($start = null, $end = null, $tableAlias = 'di') {

		$strToMysqlDateTime = function($s) { return date('Y-m-d H:i:s', strtotime($s)); };
		$strToMysqlDate     = function($s) { return date('Y-m-d', strtotime($s)); };
		$startOfDay         = function($s) { return date('Y-m-d H:i:s', strtotime("midnight", strtotime($s))); };
		$endOfDay           = function($s) use ($startOfDay) { return date('Y-m-d H:i:s', strtotime("tomorrow", strtotime($startOfDay($s))) - 1); };

		if ($start && $end) {
			$timerange = "{$tableAlias}.accessed_at BETWEEN '{$strToMysqlDateTime($startOfDay($start))}' AND '{$strToMysqlDateTime($endOfDay($end))}'";
		} elseif ($start) {
			$timerange = "DATE({$tableAlias}.accessed_at) = '{$strToMysqlDate($start)}'";
		} else {
			$timerange = "1 = 1";
		}

		return $timerange;
	}
}

DownloadIntentClean::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
DownloadIntentClean::property( 'user_agent_id', 'INT' );
DownloadIntentClean::property( 'media_file_id', 'INT' );
DownloadIntentClean::property( 'request_id', 'VARCHAR(32)' );
DownloadIntentClean::property( 'accessed_at', 'DATETIME' );
DownloadIntentClean::property( 'source', 'VARCHAR(255)' );
DownloadIntentClean::property( 'context', 'VARCHAR(255)' );
DownloadIntentClean::property( 'geo_area_id', 'INT' );
DownloadIntentClean::property( 'lat', 'FLOAT' );
DownloadIntentClean::property( 'lng', 'FLOAT' );
DownloadIntentClean::property( 'httprange', 'VARCHAR(255)' );
DownloadIntentClean::property( 'hours_since_release', 'INT' );
