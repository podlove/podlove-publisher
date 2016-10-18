<?php
namespace Podlove\Jobs;

use \Podlove\Log;
use Podlove\Jobs\Jobs;
use Podlove\Model\Job;

/**
 * WP Cron based job runner
 * 
 * EXAMPLES
 * 
 *     use \Podlove\Jobs\CronJobRunner;
 * 
 *     CronJobRunner::create_job('\Podlove\Jobs\CountingJob', ['from' => 0, 'to' => '42']);
 * 
 */
class CronJobRunner {

	const MAX_SECONDS_PER_REQUEST = 10;

    /**
     * @var float
     */
    public static $requestTime;

	public static function init() {
		add_action('cron_job_runner', [__CLASS__, 'run_job'], 10, 2);

		add_filter('cron_schedules', [__CLASS__, 'add_cron_schedules']);

		if (!wp_next_scheduled('cron_job_worker')) {
			wp_schedule_event(time(), '1min', 'cron_job_worker');
		}
		add_action('cron_job_worker', [__CLASS__, 'work_jobs']);

		// ---------- START block: second system system that runs worker all the time

		// Maybe spawning worker jobs every 5 seconds is more work than simply running
		// the worker on every request. Takes load off the WP Cron system and might 
		// help increase all-in-all stability. Let's give it a try.

		// if (!defined('DOING_AJAX') && !defined('DOING_CRON') && empty($_POST)) {
		// 	self::work_jobs();
		// } else {
		// 	// only if jobs were not started anyway, see if a cron wants to start it
		// 	add_action('cron_job_worker', [__CLASS__, 'work_jobs']);
		// }
		// 
		// ---------- END block

		// ---------- START block: old system that runs worker via cron every 5 seconds
		// 
		// add_action('cron_job_worker', [__CLASS__, 'work_jobs']);

		// worker checks every few seconds if there are jobs to handle
		// if (!wp_next_scheduled('cron_job_worker')) {
		// 	wp_schedule_single_event(time() + 5, 'cron_job_worker');
		// }
		// 
		// ---------- END block
	}

	public static function add_cron_schedules($schedules) {

		$schedules['1min'] = [
			'interval' => 60,
			'display'  => __('Every minute')
		];

		return $schedules;
	}

	/**
	 * Create new job
	 * 
	 * @param  JobTrait $job
	 * @param  array  $args
	 */
	public static function create_job($job_class, $args = []) {

		// for now, only accept one unfinished instance per job
		// maybe make this behaviour configurable per job

		$unfinished = Job::find_one_recent_unfinished_job($job_class);
		if ($unfinished) {
			\Podlove\Log::get()->addDebug('[job] did not start ' . $job_class . ' because a job of this type is already running (id ' . $unfinished->id . ')');
			return NULL;
		}

		$job = (new $job_class($args))->init();

		// immediately wake up worker for less waiting time
		wp_schedule_single_event(time(), 'cron_job_worker');
		
		\Podlove\Log::get()->addDebug('[job] [id ' . $job->get_job()->id . '] start ' . $job_class);

		return $job;
	}

	public static function spawn($job) {
		wp_schedule_single_event(time() - 1, 'cron_job_runner', [$job->id, time()]);
	}

	public static function work_jobs() {

		set_transient('podlove_jobs_last_spawn_worker', time(), DAY_IN_SECONDS);

		if (self::is_process_running()) {
			return;
		}

		self::lock_process();

		// find job to be done
		$job = Job::find_next_in_queue();

		if ($job) {
			self::spawn($job);
		} else {
			self::unlock_process();
		}
	}

	/**
	 * Is process running
	 *
	 * Check whether the current process is already running
	 * in a background process.
	 */
	protected static function is_process_running() {
		if ( get_transient( 'podlove_process_lock' ) ) {
			// Process already running.
			return true;
		}
		return false;
	}
	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * The duration should be greater than that defined in self::MAX_SECONDS_PER_REQUEST.
	 */
	protected static function lock_process() {
		$lock_duration = self::MAX_SECONDS_PER_REQUEST + 10;
		$lock_duration = apply_filters( 'podlove_queue_lock_time', $lock_duration );
		set_transient( 'podlove_process_lock', microtime(), $lock_duration );
	}
	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 *
	 * @return $this
	 */
	protected static function unlock_process() {
		delete_transient( 'podlove_process_lock' );
	}

	public static function run_job($job_id, $spawn_time) {

		set_transient('podlove_jobs_last_spawn_runner', time(), DAY_IN_SECONDS);

		$job = Job::load($job_id);

		if (!$job) {
			\Podlove\Log::get()->addDebug('[job] [id ' . $job_id . '] runner tried to run job but it does not exist');
			self::unlock_process();
			return;
		}

		$job->get_job()->increase_wakeup_count();

		while (!$job->is_finished() && self::should_run_another_job()) {
			$job->step();
			// \Podlove\Log::get()->addDebug('[job] [id ' . $job_id . '] step');
		}

		if ($job->is_finished()) {
			\Podlove\Log::get()->addDebug('[job] [id ' . $job_id . '] done');
		}

		$job->get_job()->increase_sleep_count();
		self::unlock_process();

		// after finishing, spawn a new worker process
		wp_schedule_single_event(time(), 'cron_job_worker');
	}

	/**
	 * Determine if another job should run based on request duration.
	 * 
	 * I tried getrusage() but it does not return useful time values. It looks 
	 * like PHP processes can get reused, so requests don't reliably start with
	 * 0 seconds system/user time.
	 * The microtime approach is "good enough", just don't try to use the allowed
	 * 30 seconds and we should be fine.
	 * 
	 * @return boolean
	 */
	public static function should_run_another_job() {

		$elapsed = microtime(true) - self::$requestTime;

		return $elapsed < self::MAX_SECONDS_PER_REQUEST;
	}

}

if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
    CronJobRunner::$requestTime = $_SERVER['REQUEST_TIME_FLOAT'];
} elseif (isset($_SERVER['REQUEST_TIME'])) {
    CronJobRunner::$requestTime = $_SERVER['REQUEST_TIME'];
} else {
    CronJobRunner::$requestTime = microtime(true);
}
