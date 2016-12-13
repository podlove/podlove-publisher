<?php
namespace Podlove\Analytics;

use \Podlove\Model;
use \Podlove\Jobs\CronJobRunner;

class DownloadSumsCalculator {

	public static function init() {
		self::schedule_crons();

		add_action('podlove_calc_download_sums', array(__CLASS__, 'calc_download_sums'));
	}

	public static function schedule_crons() {
		if (!wp_next_scheduled('podlove_calc_download_sums'))
			wp_schedule_event(time(), 'twicedaily', 'podlove_calc_download_sums');
	}

	public static function calc_download_sums() {
		$job = CronJobRunner::create_job('\Podlove\Jobs\DownloadTimedAggregatorJob', [
			'force' => false
		]);
	}

}
