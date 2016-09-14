<?php 
namespace Podlove\Jobs;

class JobCleaner {
	
	public static function init() {
		add_action('podlove_jobs_clean', [__CLASS__, 'podlove_jobs_clean']);

	    if (!wp_next_scheduled('podlove_jobs_clean')) {
			wp_schedule_event(time(), 'hourly', 'podlove_jobs_clean');
	    }
	}

	public static function podlove_jobs_clean() {
		Jobs::clean();
	}

}
