<?php
namespace Podlove\Analytics;

use Podlove\Model;
use Podlove\Jobs\CronJobRunner;

/**
 * Cron manager to salt request_ids in DownloadIntentClean table
 */
class SaltShaker {

	public static function init()
	{
		self::schedule_crons();

		add_action('podlove_salt_download_intents', array(__CLASS__, 'cleanup_download_intents'));
	}

	public static function schedule_crons() {
		if (!wp_next_scheduled('podlove_salt_download_intents')) {
			$three_am = strtotime(date("Y-m-d") . " 03:00:00");
			wp_schedule_event($three_am, 'daily', 'podlove_salt_download_intents');
		}
	}

	public static function cleanup_download_intents() {
		CronJobRunner::create_job('\Podlove\Jobs\RequestIdRehashJob');
	}

}
