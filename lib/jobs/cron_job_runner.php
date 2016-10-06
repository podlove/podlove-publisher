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
		add_action('cron_job_worker', [__CLASS__, 'work_jobs']);

		\Podlove\add_tools_section('jobs', __('Background Jobs', 'podlove-podcasting-plugin-for-wordpress'), [__CLASS__, 'render_jobs_overview']);

		// worker checks every few seconds if there are jobs to handle
		if (!wp_next_scheduled('cron_job_worker')) {
			wp_schedule_single_event(time() + 5, 'cron_job_worker');
		}
	}

	private static function get_recently_finished_jobs() {
		$sql = '
		SELECT
			*
		FROM
			' . Job::table_name() .  '
		WHERE
			steps_total <= steps_progress
		ORDER BY created_at DESC
		LIMIT 0, 10
		';

		return Job::find_all_by_sql($sql);
	}

	private static function get_running_jobs() {
		$sql = '
		SELECT
			*
		FROM
			' . Job::table_name() .  '
		WHERE
			steps_total > steps_progress
		ORDER BY created_at DESC
		';

		return Job::find_all_by_sql($sql);
	}

	public static function render_jobs_overview() {
		$jobs = self::get_running_jobs();
		$finished_jobs = self::get_recently_finished_jobs();
		?>

		<?php if (count($jobs)): ?>
		<h4><?php echo __('Running', 'podlove-podcasting-plugin-for-wordpress'); ?></h4>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php echo __('Job Name', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
					<th><?php echo __('Progress', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
					<th><?php echo __('Created', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
					<th><?php echo __('Last Progress', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($jobs as $job): ?>
					<tr>
						<td>
							<?php echo $job->class; ?>
						</td>
						<td>
							<?php 
							echo sprintf(
								"%d/%d (%d%%)", 
								$job->steps_progress, 
								$job->steps_total, 
								100 * ($job->steps_progress / $job->steps_total))
							?>
						</td>
						<td>
							<?php echo sprintf(__('%s ago'), human_time_diff(strtotime($job->updated_at))); ?>
						</td>
						<td>
							<?php echo sprintf(__('%s sec ago'), (time() - strtotime($job->updated_at))); ?>
						</td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>		
		<?php else: ?>
			<?php echo __('No jobs are running.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		<?php endif; ?>

		<?php if (count($finished_jobs)): ?>
		<h4><?php echo __('Recently Finished', 'podlove-podcasting-plugin-for-wordpress'); ?></h4>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php echo __('Job Name', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
					<th><?php echo __('Finished', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
					<th>
						<?php echo __('Duration', 'podlove-podcasting-plugin-for-wordpress') ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($finished_jobs as $job_id => $job): ?>
					<tr>
						<td>
							<?php echo $job->class; ?>
						</td>
						<td>
							<?php echo sprintf(__('%s ago'), human_time_diff(strtotime($job->updated_at))); ?>
						</td>
						<td>
							<?php echo sprintf(__('%s seconds', 'podlove-podcasting-plugin-for-wordpress'), round($job->active_run_time, 2)); ?>
						</td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>		
		<?php else: ?>
			<?php echo __('No jobs were run recently.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Create new job
	 * 
	 * @param  JobTrait $job
	 * @param  array  $args
	 */
	public static function create_job($job_class, $args = []) {

		$job = (new $job_class($args))->init();

		// immediately wake up worker for less waiting time
		wp_schedule_single_event(time(), 'cron_job_worker');
		
		\Podlove\Log::get()->addDebug('[job] start ' . $job_class);

		return $job;
	}

	public static function spawn($job) {
		wp_schedule_single_event(time() - 1, 'cron_job_runner', [$job->id, 0]);
	}

	public static function work_jobs() {

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
		if ( get_site_transient( 'podlove_process_lock' ) ) {
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
		$lock_duration = 60; // 1 minute
		$lock_duration = apply_filters( 'podlove_queue_lock_time', $lock_duration );
		set_site_transient( 'podlove_process_lock', microtime(), $lock_duration );
	}
	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 *
	 * @return $this
	 */
	protected static function unlock_process() {
		delete_site_transient( 'podlove_process_lock' );
	}

	public static function run_job($job_id, $call_count) {

		$job = Job::load($job_id);

		if (!$job) {
			\Podlove\Log::get()->addDebug('[job] runner tried to run job ' . $job_id . ' but it does not exist');
			self::unlock_process();
			return;
		}

		while (!$job->is_finished() && self::should_run_another_job()) {
			$job->step();
			\Podlove\Log::get()->addDebug('[job] step ' . $job_id);
		}

		if ($job->is_finished()) {
			\Podlove\Log::get()->addDebug('[job] done ' . $job_id);
		}

		self::unlock_process();
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
