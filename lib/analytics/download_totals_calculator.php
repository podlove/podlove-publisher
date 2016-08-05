<?php
namespace Podlove\Analytics;

use \Podlove\Model;
use \Podlove\Jobs\CronJobRunner;

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
		$job = CronJobRunner::create_job('\Podlove\Jobs\DownloadTotalsAggregatorJob');
		CronJobRunner::run($job);
	}
}
