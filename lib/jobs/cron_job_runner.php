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

    /**
     * @var float
     */
    public static $requestTime;

	public static function init() {
		add_filter('cron_schedules', [__CLASS__, 'add_cron_schedules']);

		if (!wp_next_scheduled('cron_job_worker')) {
			wp_schedule_event(time(), '1min', 'cron_job_worker');
		}
		add_action('cron_job_worker', [__CLASS__, 'work_jobs']);
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

	public static function work_jobs($ignore_lock = false) {

		set_transient('podlove_jobs_last_spawn_worker', time(), DAY_IN_SECONDS);

		if (!$ignore_lock && self::is_process_running()) {
			return;
		}

		self::lock_process();

		// find job to be done
		$job = Job::find_next_in_queue();

		if ($job) {
			self::run_job($job->id, time());
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
	 * Maximum Seconds per Request
	 * 
	 * Duration after which no further job steps are started. The sum of 
	 * max_seconds_per_request and lock_duration_buffer should not exceed
	 * PHP ini value `max_execution_time` which defaults to 30 on most systems.
	 * 
	 * When running via the command line (or system cron), `max_execution_time`
	 * is often much higher or deactivated. In these cases, much higher execution
	 * times can be set for speedy results.
	 * 
	 * Example cron call while overriding time limit:
	 * 
	 *   sudo PODLOVE_JOB_MAX_SECONDS_PER_REQUEST=40 -u www-data php /var/www/html/wp/wp-cron.php >>/var/log/cron.log 2>&1
	 * 
	 * @return int
	 */
    public static function max_seconds_per_request()
    {
    	$default = isset($_SERVER['PODLOVE_JOB_MAX_SECONDS_PER_REQUEST']) ? $_SERVER['PODLOVE_JOB_MAX_SECONDS_PER_REQUEST'] : 20;
    	return apply_filters('podlove_job_max_seconds_per_request', $default);
    }

    /**
     * Lock Duration Buffer
     * 
     * A new job step is only started when the elapsed time is below 
     * max_seconds_per_request. However, a step might take a while to complete
     * and exceed max_seconds_per_request. The buffer should be at least as big
     * as the longest expected time for one step of any job to avoid two
     * job processes running at the same time.
     * 
     * @return int
     */
    public static function lock_duration_buffer()
    {
    	$default = isset($_SERVER['PODLOVE_JOB_LOCK_DURATION_BUFFER']) ? $_SERVER['PODLOVE_JOB_LOCK_DURATION_BUFFER'] : 5;
    	return apply_filters('podlove_job_lock_duration_buffer', $default);
    }

	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * The duration should be greater than that defined in self::max_seconds_per_request().
	 */
	protected static function lock_process() {
		$lock_duration = self::max_seconds_per_request() + self::lock_duration_buffer();
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

		// abort jobs that cannot finish
		$created = strtotime($job->get_job()->created_at);
		$diff = time() - $created;

		if ($diff > HOUR_IN_SECONDS * 4) {
			$job->get_job()->delete();
			\Podlove\Log::get()->addWarning('[job] [id ' . $job_id . '] "' . $job->title() . '" aborted because it ran too long');
			self::unlock_process();
			return;
		}

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

		if (self::should_run_another_job()) {
			self::work_jobs();
		} else {
			// after finishing, spawn a new worker process
			wp_schedule_single_event(time(), 'cron_job_worker');
		}
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

		return $elapsed < self::max_seconds_per_request();
	}

}

if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
    CronJobRunner::$requestTime = $_SERVER['REQUEST_TIME_FLOAT'];
} elseif (isset($_SERVER['REQUEST_TIME'])) {
    CronJobRunner::$requestTime = $_SERVER['REQUEST_TIME'];
} else {
    CronJobRunner::$requestTime = microtime(true);
}
