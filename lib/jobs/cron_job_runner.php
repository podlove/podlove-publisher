<?php
namespace Podlove\Jobs;

use \Podlove\Log;

/**
 * WP Cron based job runner
 * 
 * EXAMPLES
 * 
 *     $runner = new \Podlove\Jobs\CronJobRunner;
 *     $runner->create_job('\Podlove\Jobs\CountingJob', ['from' => 0, 'to' => '42']);
 *     $runner->run();
 * 
 * TODO
 * - when a job is done and it produced artefacts (like a file after an export), how do I get it?
 * - log how much time each step required
 * - try to spawn a new cron immediately after the current one is done
 * - do more than one step at a time; just keep an eye on total script running time
 */
class CronJobRunner {

	private $job;

	public static function init() {
		add_action('cron_job_runner', [__CLASS__, 'run_job'], 10, 2);

		\Podlove\add_tools_section('jobs', __('Background Jobs', 'podlove-podcasting-plugin-for-wordpress'), [__CLASS__, 'render_jobs_overview']);
	}

	private static function get_running_jobs() {

		$jobs = [];
		$crons = _get_cron_array();

		if (!is_array($crons))
			return [];

		foreach ($crons as $time => $cron_list) {
			foreach ($cron_list as $cron_name => $cron) {
				if ($cron_name == 'cron_job_runner') {
					foreach ($cron as $cron_id => $cron_data) {
						
						$job_id = $cron_data['args'][0];

						$jobs[$job_id] = [
							'call_count' => $cron_data['args'][1],
							'time' => $time,
							'job_raw' => Jobs::get($job_id),
							'job' => Jobs::load($job_id),
						];

					}
				}
			}
		}
		
		return $jobs;
	}

	public static function render_jobs_overview() {
		?>
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
				<?php foreach (self::get_running_jobs() as $job_id => $job): ?>
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
		<?php
	}

	/**
	 * Create new job
	 * 
	 * @param  JobTrait $job
	 * @param  array  $args
	 */
	public function create_job($job_class, $args = []) {
		$this->job = (new $job_class($args))->init();
		$this->job->step();
		
		\Podlove\Log::get()->addDebug('[job] start ' . $job_class);

		return $this;
	}

	public function run() {
		wp_schedule_single_event(time() - 1, 'cron_job_runner', [$this->job->get_job_id(), 0]);
	}

	public static function run_job($job_id, $call_count) {
		$job = Jobs::load($job_id);
		$job->step();

		\Podlove\Log::get()->addDebug('[job] step ' . $job_id, $job->get_status());

		if (!$job->is_finished()) {
			wp_schedule_single_event(time() - 1, 'cron_job_runner', [$job_id, $call_count+1]);
		}
	}

}
