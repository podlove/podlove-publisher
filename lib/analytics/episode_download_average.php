<?php 
namespace Podlove\Analytics;

/**
 * Calculate download averages for episodes
 *
 * Calculating EDAs is costly, that's why intermediate results are calculated
 * and stored separately. The goal is to generate a graph displaying average
 * downloads over all episodes, relative to each release date. Each episode
 * stores the download data for the first n hours as a post_meta.
 */
class EpisodeDownloadAverage
{
	const HOURS_TO_CALCULATE = 800; // roughly a month

	public static function init()
	{
		self::schedule_crons();

		add_action('recalculate_episode_download_average', array(__CLASS__, 'recalculate_episode_download_average'));
	}

	public static function schedule_crons() {
		if (!wp_next_scheduled('recalculate_episode_download_average'))
			wp_schedule_event(time(), 'daily', 'recalculate_episode_download_average');
	}

	public static function recalculate_episode_download_average()
	{
		set_time_limit(1800); // set max_execution_time to half an hour

		$query = new \WP_Query(array(
			'post_type' => 'podcast',
			'post_status' => array('publish', 'private'),
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => '_podlove_eda_complete',
					'compare' => 'NOT EXISTS'
				)
			)
		));

		while ($query->have_posts()) {
			$query->the_post();
			$post_id = get_the_ID();
			$episode = \Podlove\Model\Episode::find_or_create_by_post_id($post_id);
			$downloads = self::get_downloads_per_hour_for_episode($episode->id);
			update_post_meta($post_id, '_podlove_eda_downloads', implode(',', $downloads));

			if (count($downloads) >= self::HOURS_TO_CALCULATE) {
				update_post_meta($post_id, '_podlove_eda_complete', 1);
			}
		}

		wp_reset_postdata();
	}

	private static function get_downloads_per_hour_for_episode($episode_id) {
		global $wpdb;

		$sql = "
			SELECT
			  	COUNT(*) downloads, access_hour
			FROM (
				SELECT
					media_file_id, DATE_FORMAT(accessed_at, '%%Y-%%m-%%d %%H') access_hour
				FROM
					wp_podlove_downloadintent di 
					INNER JOIN wp_podlove_mediafile mf ON mf.id = di.media_file_id
					WHERE episode_id = %d
				GROUP BY media_file_id, request_id, access_hour
			) di
			GROUP BY access_hour
			ORDER BY access_hour
			LIMIT %d
		";

		$data = $wpdb->get_results(
			$wpdb->prepare($sql, $episode_id, self::HOURS_TO_CALCULATE),
			ARRAY_A
		);

		if ($data) {
			return array_column(self::add_missing_hours($data), 'downloads');
		} else {
			return array();			
		}
	}

	/**
	 * $data is an associative array with downloads and datetime column in hour-accuracy.
	 * This method adds 0-download-entries for missing hours.
	 *
	 * @todo add entries *before* first item (actually ... for current use case not required)
	 */
	private static function add_missing_hours($data)
	{
		$time_format = "Y-m-d H";

		return array_reduce($data, function($agg, $item) use ($time_format) {

			if (empty($agg)) {
				$agg[] = $item;
			} else {
				$last_item = end($agg);
				$last_time = \DateTime::createFromFormat($time_format, $last_item['access_hour']);
				$cur_time  = \DateTime::createFromFormat($time_format, $item['access_hour']);
				$date_diff = $last_time->diff($cur_time);
				$hour_diff = $date_diff->h + $date_diff->d * 24;

				// fill with 0 entries for every missing hour
				for ($i=$hour_diff; $i > 1; $i--) { 
					$last_time->add(\DateInterval::createFromDateString("1 hour"));
					$agg[] = array(
						'downloads' => 0,
						'access_hour' => $last_time->format($time_format)
					);
				}
				// add the current item
				$agg[] = $item;
			}

			return $agg;
		}, array());
	}
}
