<?php
namespace Podlove\Model;

class DownloadIntent extends Base {

	public static function top_episode_ids($start, $end = "now") {
		global $wpdb;

		$dateStart = date("Y-m-d", strtotime($start));
		$dateEnd   = date("Y-m-d", strtotime($end));

		$sql = "
			SELECT
				episode_id, COUNT(*) downloads
			FROM
				" . DownloadIntent::table_name() . " di
				JOIN " . MediaFile::table_name() . " mf ON mf.id = di.media_file_id
			WHERE
				di.accessed_at BETWEEN %s AND %s
			GROUP BY
				episode_id
			ORDER BY
				downloads DESC
			LIMIT
				0, 3
		";

		return $wpdb->get_col(
			$wpdb->prepare($sql, $dateStart, $dateEnd)
		);
	}

	public static function daily_episode_totals($episode_id, $start, $end = "now") {
		global $wpdb;

		$dateStart = date("Y-m-d", strtotime($start));
		$dateEnd   = date("Y-m-d", strtotime($end));

		$sql = "
			SELECT
				DATE(di.accessed_at) theday, COUNT(*) downloads
			FROM
				" . DownloadIntent::table_name() . " di
				JOIN " . MediaFile::table_name() . " mf ON mf.id = di.media_file_id
			WHERE
				episode_id = %d
				AND
				di.accessed_at BETWEEN %s AND %s
			GROUP BY
				theday
			ORDER BY
				theday
		";

		return $wpdb->get_results(
			$wpdb->prepare($sql, $episode_id, $dateStart, $dateEnd)
		);
	}

	public static function daily_totals($start, $end = "now", $exclude_episode_ids = array()) {
		global $wpdb;

		$dateStart = date("Y-m-d", strtotime($start));
		$dateEnd   = date("Y-m-d", strtotime($end));

		// ensure all values are ints
		$exclude_episode_ids = array_map(function($x) { return (int) $x; }, $exclude_episode_ids);
		// filter out zero values
		$exclude_episode_ids = array_filter($exclude_episode_ids);

		$exclude_sql = "";
		if (count($exclude_episode_ids)) {
			$exclude_sql = "episode_id NOT IN (" . implode(",", $exclude_episode_ids) . ") AND ";
		}

		$sql = "
			SELECT
				DATE(di.accessed_at) theday, COUNT(*) downloads
			FROM
				" . DownloadIntent::table_name() . " di
				JOIN " . MediaFile::table_name() . " mf ON mf.id = di.media_file_id
			WHERE
				$exclude_sql
				di.accessed_at BETWEEN %s AND %s
			GROUP BY
				theday
			ORDER BY
				theday
		";

		return $wpdb->get_results(
			$wpdb->prepare($sql, $dateStart, $dateEnd)
		);
	}

	public static function total_by_episode_id($episode_id) {
		global $wpdb;

		$sql = "
			SELECT
				COUNT(*)
			FROM
				" . DownloadIntent::table_name() . "
			WHERE
				media_file_id IN (
					SELECT id FROM " . MediaFile::table_name() . " WHERE episode_id = %d
				)
		";

		return $wpdb->get_var(
			$wpdb->prepare($sql, $episode_id)
		);
	}

}

DownloadIntent::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
DownloadIntent::property( 'user_agent_id', 'INT' );
DownloadIntent::property( 'media_file_id', 'INT' );
DownloadIntent::property( 'accessed_at', 'DATETIME' );
DownloadIntent::property( 'source', 'VARCHAR(255)' );
DownloadIntent::property( 'context', 'VARCHAR(255)' );
DownloadIntent::property( 'ip', 'VARCHAR(255)' );