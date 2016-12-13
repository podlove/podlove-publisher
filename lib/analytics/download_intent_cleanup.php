<?php
namespace Podlove\Analytics;

use Podlove\Model;
use Podlove\Jobs\CronJobRunner;

/**
 * Cron manager to fill DownloadIntentClean table
 */
class DownloadIntentCleanup {

	public static function init()
	{
		self::schedule_crons();

		add_action('podlove_cleanup_download_intents', array(__CLASS__, 'cleanup_download_intents'));
	}

	public static function schedule_crons() {
		if (!wp_next_scheduled('podlove_cleanup_download_intents'))
			wp_schedule_event(time(), 'hourly', 'podlove_cleanup_download_intents');
	}

	public static function cleanup_download_intents() {
		CronJobRunner::create_job('\Podlove\Jobs\DownloadIntentCleanupJob', ['delete_all' => false]);
	}

}
