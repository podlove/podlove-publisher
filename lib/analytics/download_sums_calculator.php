<?php
namespace Podlove\Analytics;

use \Podlove\Model;

class DownloadSumsCalculator {

	public static function init() {
		self::schedule_crons();

		add_action('podlove_calc_download_sums', array(__CLASS__, 'calc_download_sums'));
	}

	public static function schedule_crons() {
		if (!wp_next_scheduled('podlove_calc_download_sums'))
			wp_schedule_event(time(), 'twicedaily', 'podlove_calc_download_sums');
	}

	public static function calc_download_sums($force = false) {
		global $wpdb;

		\PHP_Timer::start();
		$groupings = \Podlove\Analytics\DownloadSumsCalculator::groupings();

		foreach (Model\Podcast::get()->episodes() as $episode) {
			
			$max_hsr = Model\DownloadIntentClean::actual_episode_age_in_hours($episode->id);

			foreach ($groupings as $key => $hours) {

				// skip already calculated fields IF override is not forced
				if (!$force && get_post_meta($episode->post_id, '_podlove_downloads_' . $key, true) != '')
					continue;

				// skip fields that cannot be calculated yet
				if ($max_hsr <= $hours)
					continue;

				$sql = $wpdb->prepare(
					'SELECT
					  COUNT(*)
					FROM ' . Model\DownloadIntentClean::table_name() . ' di
					INNER JOIN ' . Model\MediaFile::table_name() . ' mf ON mf.id = di.media_file_id
					INNER JOIN ' . Model\Episode::table_name() . ' e ON mf.episode_id = e.id
					WHERE e.id = %d AND hours_since_release <= %d',
					$episode->id, $hours
				);
				$downloads = $wpdb->get_var($sql);

				if ($downloads && is_numeric($downloads)) {
					update_post_meta($episode->post_id, '_podlove_downloads_' . $key, $downloads);
				}
			}
		}

		$time = \PHP_Timer::stop();
		\Podlove\Log::get()->addInfo(sprintf('Finished calculating download sums in %s', \PHP_Timer::secondsToTimeString($time)));
	}

	public static function groupings()
	{
		return  [
			'4w' => 24 * 7 * 4,
			'3w' => 24 * 7 * 3,
			'2w' => 24 * 7 * 2,
			'1w' => 24 * 7,
			'6d' => 24 * 6,
			'5d' => 24 * 5,
			'4d' => 24 * 4,
			'3d' => 24 * 3,
			'2d' => 24 * 2,
			'1d' => 24
		];
	}
}
