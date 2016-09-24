<?php
namespace Podlove\Jobs;

use \Podlove\Log;

/**
 * WP Cron based job runner
 * 
 * EXAMPLES
 * 
 *     use \Podlove\Jobs\CronJobRunner;
 * 
 *     $job = CronJobRunner::create_job('\Podlove\Jobs\CountingJob', ['from' => 0, 'to' => '42']);
 *     CronJobRunner::run($job);
 * 
 * TODO
 * - when a job is done and it produced artefacts (like a file after an export), how do I get it?
 * - try to spawn a new cron immediately after the current one is done
 * - for tools page, job-related tools should:
 *   - inform that user may navigate away
 *   - restore/show progress when user opens tools page if it's running
 *   - show when the job was last run (nevermind if by user or programmatically—is the initiator an information worth saving?)
 * - JS toolkit for starting jobs, fetching their state
 * - clean up podlove_jobs
 */
class CronJobRunner {

	const MAX_SECONDS_PER_REQUEST = 10;

    /**
     * @var float
     */
    public static $requestTime;

	public static function init() {
		add_action('cron_job_runner', [__CLASS__, 'run_job'], 10, 2);

		\Podlove\add_tools_section('jobs', __('Background Jobs', 'podlove-podcasting-plugin-for-wordpress'), [__CLASS__, 'render_jobs_overview']);
	}

	private static function get_recently_finished_jobs() {

		$jobs = [];

		foreach (Jobs::all() as $job_id => $job_raw) {

			$job = Jobs::load($job_id);

			if (!$job) {
				continue;
			}

			if (!$job->is_finished())
				continue;

			if (time() - $job->updated_at > DAY_IN_SECONDS)
				continue;

			$jobs[$job_id] = [
				'job_raw' => $job_raw,
				'job' => $job,
			];
		}

		return $jobs;
	}

	// @todo: running jobs are _not_ returned by _get_cron_array
	// => loop like self::get_recently_finished_jobs() instead and only match
	//    to cron array inside loop for time (it's not in the cron array? then the
	//    job is running right now!)
	private static function get_running_jobs() {

		$jobs = [];
		$crons = _get_cron_array();

		if (!is_array($crons))
			return [];

		foreach ($crons as $time => $cron_list) {
			foreach ($cron_list as $cron_name => $cron) {
				if ($cron_name == 'cron_job_runner') {
					foreach ($cron as $cron_id => $cron_data) {
						
						$job_id  = $cron_data['args'][0];
						$job_raw = Jobs::get($job_id);
						$job     = Jobs::load($job_id);

						if ($job && $job_raw) {
							$jobs[$job_id] = [
								'call_count' => $cron_data['args'][1],
								'time' => $time,
								'job_raw' => $job_raw,
								'job' => $job,
							];
						}
					}
				}
			}
		}
		
		return $jobs;
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
					<th><?php echo __('Status', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
					<th><?php echo __('Progress', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
					<th><?php echo __('Created', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
					<th><?php echo __('Last Progress', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($jobs as $job_id => $job): ?>
					<?php $status = $job['job']->get_status(); ?>
					<tr>
						<td>
							<?php echo $job['job_raw']['class']; ?>
						</td>
						<td>
							<?php echo $status['text'] ?>
						</td>
						<td>
							<?php echo sprintf("%d/%d (%d%%)", $status['progress'], $status['total'], $status['percent']) ?>
						</td>
						<td>
							<?php echo sprintf(__('%s ago'), human_time_diff($job['job']->created_at)); ?>
						</td>
						<td>
							<?php echo sprintf(__('%s sec ago'), (time() - $job['job']->updated_at)); ?>
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
					<?php $status = $job['job']->get_status(); ?>
					<tr>
						<td>
							<?php echo $job['job_raw']['class']; ?>
						</td>
						<td>
							<?php echo sprintf(__('%s ago'), human_time_diff($job['job']->updated_at)); ?>
						</td>
						<td>
							<?php echo sprintf(__('%s seconds', 'podlove-podcasting-plugin-for-wordpress'), round($job['job']->get_status()['time'], 2)); ?>
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
		
		\Podlove\Log::get()->addDebug('[job] start ' . $job_class);

		return $job;
	}

	public static function run($job) {
		wp_schedule_single_event(time() - 1, 'cron_job_runner', [$job->get_job_id(), 0]);
	}

	public static function run_job($job_id, $call_count) {

		$job = Jobs::load($job_id);

		if (!$job) {
			\Podlove\Log::get()->addDebug('[job] runner tried to run job ' . $job_id . ' but it does not exist', $job->get_status());
			return;
		}

		while (!$job->is_finished() && self::should_run_another_job()) {
			$job->step();
			\Podlove\Log::get()->addDebug('[job] step ' . $job_id, $job->get_status());
		}

		if (!$job->is_finished()) {
			wp_schedule_single_event(time() - 1, 'cron_job_runner', [$job_id, $call_count+1]);
		} else {
			\Podlove\Log::get()->addDebug('[job] done ' . $job_id, $job->get_status());
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
