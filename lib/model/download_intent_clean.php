<?php
namespace Podlove\Model;

/**
 * Contains cleaned up data of DownloadIntent table.
 */
class DownloadIntentClean extends Base
{
    public static function build()
    {
        global $wpdb;

        parent::build();

        // note: this will silently fail if it already exists
        $sql = 'CREATE INDEX accessed_at ON `%s` (accessed_at)';
        $wpdb->query(sprintf($sql, \Podlove\Model\DownloadIntentClean::table_name()));
    }

    public static function episode_age_in_hours($episode_id)
    {
        global $wpdb;

        // This query is a bit slow, ~50ms on 2MM intents table.
        // It might be acceptable if not used in a loop.
        // If the actual episode age is acceptable (rather than age in intents),
        // use the quicker alternative: `actual_episode_age_in_hours`
        return $wpdb->get_var(
            $wpdb->prepare(
                'SELECT MAX(hours_since_release)
				FROM ' . self::table_name() . ' di
				JOIN ' . MediaFile::table_name() . ' mf ON mf.id = di.media_file_id
				WHERE mf.episode_id = %d',
                $episode_id
            )
        );
    }

    public static function actual_episode_age_in_hours($episode_id)
    {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                'SELECT TIMESTAMPDIFF(HOUR, p.post_date, NOW())
				FROM `' . Episode::table_name() . '` e
				JOIN `' . $wpdb->posts . '` p ON p.ID = e.`post_id`
				WHERE e.id = %d',
                $episode_id
            )
        );
    }

    public static function top_episode_ids($start, $end = "now", $limit = 3)
    {
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
    public static function peak_download_by_episode_id($episode_id)
    {
        global $wpdb;

        $sql = "
			SELECT
				COUNT(*) downloads, DATE(accessed_at) theday
			FROM
				" . self::table_name() . " di
				INNER JOIN " . MediaFile::table_name() . " mf ON mf.id = di.media_file_id
			WHERE
				episode_id = %d
			GROUP BY theday
			ORDER BY downloads DESC
			LIMIT 0,1
		";

        return $wpdb->get_row(
            $wpdb->prepare($sql, $episode_id),
            ARRAY_A
        );
    }

    public static function total_by_episode_id($episode_id, $start = null, $end = null)
    {
        global $wpdb;

        $sql = "
			SELECT
				COUNT(*)
			FROM
				" . self::table_name() . " di
				INNER JOIN " . MediaFile::table_name() . " mf ON mf.id = di.media_file_id
			WHERE
				episode_id = %d
				AND " . self::sql_condition_from_time_strings($start, $end) . "
		";

        return $wpdb->get_var(
            $wpdb->prepare($sql, $episode_id)
        );
    }

    public static function prev_month_downloads()
    {
        global $wpdb;

        $cur_month  = date('m');
        $last_month = $cur_month - 1;
        $year       = date('Y');

        if ($last_month < 1) {
            $last_month = 12;
            $year--;
        }

        if ($last_month < 10) {
            $last_month = "0$last_month";
        }

        $last_month_time = strtotime("$year-$last_month");
        $last_month_name = date('F Y', $last_month_time);

        $where_start = (new \DateTime("$year-$last_month"))->format("Y-m-d H:i:s");
        $where_end   = (new \DateTime("last day of $year-$last_month"))->modify("+ 1 day - 1 second")->format("Y-m-d H:i:s");

        $sql = 'SELECT COUNT(*) FROM ' . self::table_name() . ' d WHERE accessed_at >= "' . $where_start . '" AND accessed_at <= "' . $where_end . '"';

        return [
            'downloads'            => $wpdb->get_var($sql),
            'time'                 => $last_month_time,
            'homan_readable_month' => $last_month_name,
        ];
    }

    public static function last_7days_downloads()
    {
        global $wpdb;

        $sql = 'SELECT COUNT(*) FROM ' . self::table_name() . ' d WHERE accessed_at > DATE_SUB(NOW(), INTERVAL 7 DAY)';

        return $wpdb->get_var($sql);
    }

    public static function last_24hours_downloads()
    {
        global $wpdb;

        $sql = 'SELECT COUNT(*) FROM ' . self::table_name() . ' d WHERE accessed_at > DATE_SUB(NOW(), INTERVAL 1 DAY)';

        return $wpdb->get_var($sql);
    }

    public static function total_downloads()
    {
        global $wpdb;

        $sql = 'SELECT SUM(meta_value) total FROM `' . $wpdb->postmeta . '` WHERE `meta_key` = "_podlove_downloads_total"';
        return $wpdb->get_var($sql);
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
    private static function sql_condition_from_time_strings($start = null, $end = null, $tableAlias = 'di')
    {

        $strToMysqlDateTime = function ($s) {return date('Y-m-d H:i:s', strtotime($s));};
        $strToMysqlDate = function ($s) {return date('Y-m-d', strtotime($s));};
        $startOfDay = function ($s) {return date('Y-m-d H:i:s', strtotime("midnight", strtotime($s)));};
        $endOfDay = function ($s) use ($startOfDay) {return date('Y-m-d H:i:s', strtotime("tomorrow", strtotime($startOfDay($s))) - 1);};

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

DownloadIntentClean::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
DownloadIntentClean::property('user_agent_id', 'INT');
DownloadIntentClean::property('media_file_id', 'INT');
DownloadIntentClean::property('request_id', 'VARCHAR(32)');
DownloadIntentClean::property('accessed_at', 'DATETIME');
DownloadIntentClean::property('source', 'VARCHAR(255)');
DownloadIntentClean::property('context', 'VARCHAR(255)');
DownloadIntentClean::property('geo_area_id', 'INT');
DownloadIntentClean::property('lat', 'FLOAT');
DownloadIntentClean::property('lng', 'FLOAT');
DownloadIntentClean::property('httprange', 'VARCHAR(255)');
DownloadIntentClean::property('hours_since_release', 'INT');
