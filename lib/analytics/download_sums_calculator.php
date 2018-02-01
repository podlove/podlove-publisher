<?php
namespace Podlove\Analytics;

use \Podlove\Model;
use \Podlove\Jobs\CronJobRunner;

class DownloadSumsCalculator {

	public static function init() {
		self::schedule_crons();

		add_action('podlove_calc_hourly_download_sums', [__CLASS__, 'calc_hourly_download_sums']);
		add_action('podlove_calc_daily_download_sums', [__CLASS__, 'calc_daily_download_sums']);
	}

	public static function schedule_crons() {
		if (!wp_next_scheduled('podlove_calc_hourly_download_sums'))
			wp_schedule_event(time(), 'hourly', 'podlove_calc_hourly_download_sums');

		if (!wp_next_scheduled('podlove_calc_daily_download_sums'))
			wp_schedule_event(time(), 'daily', 'podlove_calc_daily_download_sums');
	}

	public static function calc_hourly_download_sums() {
		$job = CronJobRunner::create_job('\Podlove\Jobs\DownloadTimedAggregatorJob', [
			'force' => false
		]);
	}

	public static function calc_daily_download_sums() {
		$job = CronJobRunner::create_job('\Podlove\Jobs\DownloadTimedAggregatorJob', [
			'force' => true
		]);
	}
}
