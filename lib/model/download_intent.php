<?php
namespace Podlove\Model;

/**
 * Raw download intent data.
 * 
 * If you want to run analytics queries, you probably want to use
 * the DownloadIntentClean table.
 * 
 */
class DownloadIntent extends Base {

	public static function top_episode_ids($start, $end = "now", $limit = 3) {
		global $wpdb;

		$sql = "
			SELECT
				episode_id, COUNT(*) downloads
			FROM
				" . DownloadIntent::table_name() . " di
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

	public static function daily_episode_totals($episode_id, $start, $end = "now") {
		global $wpdb;

		$sql = "
			SELECT
				DATE(di.accessed_at) theday, COUNT(*) downloads
			FROM
				" . DownloadIntent::table_name() . " di
				JOIN " . MediaFile::table_name() . " mf ON mf.id = di.media_file_id
				JOIN " . Episode::table_name() . " e ON e.id = mf.episode_id
			WHERE
				episode_id = %d
				AND
				" . self::sql_condition_from_time_strings($start, $end) . "
			GROUP BY
				theday
			ORDER BY
				theday
		";

		$result = $wpdb->get_results(
			$wpdb->prepare($sql, $episode_id)
		);

		return self::days_data_from_query_result($result, $start, $end);;
	}

	public static function daily_totals($start, $end = "now", $exclude_episode_ids = array()) {
		global $wpdb;

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
				JOIN " . Episode::table_name() . " e ON e.id = mf.episode_id
			WHERE
				$exclude_sql
				" . self::sql_condition_from_time_strings($start, $end) . "
			GROUP BY
				theday
			ORDER BY
				theday
		";

		$result = $wpdb->get_results($sql);

		return self::days_data_from_query_result($result, $start, $end);
	}

	public static function total_by_episode_id($episode_id, $start = null, $end = null) {
		global $wpdb;

		$sql = "
			SELECT
				COUNT(*)
			FROM
				" . DownloadIntent::table_name() . " di
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
				" . DownloadIntent::table_name() . " di
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

	private static function days_data_from_query_result($totals, $start, $end) {

		$endDay = date("Y-m-d", strtotime($end));
			
		// use theday (date) as array key
		$dayTotals = array();
		foreach ($totals as $download) {
			$dayTotals[$download->theday] = $download->downloads;
		}

		// create 0-entries for days without downloads
		$days = array();
		$day = 0;

		do {
			$currentDay = date('Y-m-d', strtotime($start . " +$day days"));

			if (isset($dayTotals[$currentDay])) {
				$days[$currentDay] = $dayTotals[$currentDay];
			} else {
				$days[$currentDay] = 0;	
			}

			$day++;
		} while ($currentDay < $endDay);

		return $days;
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


	public function add_geo_data($ip_string) {

		try {
			$reader = new \GeoIp2\Database\Reader(\Podlove\Geo_Ip::get_upload_file_path());
		} catch (\InvalidArgumentException $e) {
			return $this;
		}
		
		try {
			// geo ip lookup
			$record = $reader->city($ip_string);

			$this->lat = $record->location->latitude;
			$this->lng = $record->location->longitude;

			/**
			 * Get most specific area for given record, beginning at the given area-type.
			 *
			 * Missing records will be created on the fly, based on data in $record.
			 * 
			 * @param object $record GeoIp object
			 * @param string $type Area identifier. One of: city, subdivision, country, continent.
			 */
			$get_area = function($record, $type) use (&$get_area) {

				// get parent area for the given area-type
				$get_parent_area = function($type) use ($get_area, $record) {

					switch ($type) {
						case 'city':
							return $get_area($record, 'subdivision');
							break;
						case 'subdivision':
							return $get_area($record, 'country');
							break;
						case 'country':
							return $get_area($record, 'continent');
							break;
						case 'continent':
							// has no parent
							break;
					}

					return null;
				};

				$subRecord = $record->{$type == 'subdivision' ? 'mostSpecificSubdivision' : $type};

				if (!$subRecord->geonameId)
					return $get_parent_area($type);

				if ($area = GeoArea::find_one_by_property('geoname_id', $subRecord->geonameId))
					return $area;

				$area = new GeoArea;
				$area->geoname_id = $subRecord->geonameId;
				$area->type = $type;

				if (isset($subRecord->code)) {
					$area->code = $subRecord->code;
				} elseif (isset($subRecord->isoCode)) {
					$area->code = $subRecord->isoCode;
				}

				if ($area->type != 'continent') {
					$parent_area     = $get_parent_area($area->type);
					$area->parent_id = $parent_area->id;
				}

				$area->save();

				// save name and translations
				foreach ($subRecord->names as $lang => $name) {
					$n           = new GeoAreaName;
					$n->area_id  = $area->id;
					$n->language = $lang;
					$n->name     = $name;
					$n->save();
				}

				return $area;
			};

			$area = $get_area($record, 'city');

			$this->geo_area_id = $area->id;
		} catch (\GeoIp2\Exception\AddressNotFoundException $e) {
			// geo lookup might fail, but that's not grave		
		}

		return $this;
	}

}

DownloadIntent::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
DownloadIntent::property( 'user_agent_id', 'INT' );
DownloadIntent::property( 'media_file_id', 'INT' );
DownloadIntent::property( 'request_id', 'VARCHAR(32)' );
DownloadIntent::property( 'accessed_at', 'DATETIME' );
DownloadIntent::property( 'source', 'VARCHAR(255)' );
DownloadIntent::property( 'context', 'VARCHAR(255)' );
DownloadIntent::property( 'geo_area_id', 'INT' );
DownloadIntent::property( 'lat', 'FLOAT' );
DownloadIntent::property( 'lng', 'FLOAT' );
DownloadIntent::property( 'httprange', 'VARCHAR(255)' );
