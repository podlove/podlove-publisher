<?php
namespace Podlove\Analytics;

use \Podlove\Model;

class DownloadTotalsCalculator {

	public static function init() {
		self::schedule_crons();

		add_action('podlove_calc_download_totals', array(__CLASS__, 'calc_download_totals'));
	}

	public static function schedule_crons() {
		if (!wp_next_scheduled('podlove_calc_download_totals'))
			wp_schedule_event(time(), 'hourly', 'podlove_calc_download_totals');
	}

	public static function calc_download_totals() {
		foreach (Model\Podcast::get()->episodes() as $episode) {
			$total = Model\DownloadIntentClean::total_by_episode_id($episode->id);
			if ($total) {
				update_post_meta($episode->post_id, '_podlove_downloads_total', $total);
			}
		}
	}
}
