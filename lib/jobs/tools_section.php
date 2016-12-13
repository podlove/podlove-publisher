<?php 
namespace Podlove\Jobs;

use Podlove\Model\Job;

class ToolsSection {

	public static function init()
	{
		\Podlove\add_tools_section('jobs', __('Background Jobs', 'podlove-podcasting-plugin-for-wordpress'), [__CLASS__, 'render_jobs_overview']);
		ToolsSectionCronDiagnostics::init();
	}
	
	public static function render_jobs_overview() {
		$jobs          = Job::find_running_jobs();
		$finished_jobs = Job::find_recently_finished_jobs(10);
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
							<?php $class = $job->class; ?>
							<?php echo $class::title(); ?>
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
							<?php echo sprintf(__('%s ago'), human_time_diff(strtotime($job->created_at))); ?>
						</td>
						<td>
							<?php if (!$job->wakeups || $job->created_at == $job->updated_at): ?>
								<?php echo __('Never'); ?>
							<?php else: ?>
								<?php echo sprintf(__('%s sec ago'), (time() - strtotime($job->updated_at))); ?>
							<?php endif ?>
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
							<?php $class = $job->class; ?>
							<?php echo $class::title(); ?>
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
		<?php endif; ?>

		<?php 

		$activities = [
			'worker' => [
				'title' => 'Worker Activity',
				'activity' => get_transient('podlove_jobs_last_spawn_worker'),
				'description' => 'Should not be more than two or three minutes.'
			],
			'runner' => [
				'title' => 'Runner Activity',
				'activity' => get_transient('podlove_jobs_last_spawn_runner'),
				'description' => 'May be inactive if no jobs are running. If at least one job is running, should not be more than two or three minutes.'
			]
		];

		echo '<p>';
		foreach ($activities as $activity) {
			echo $activity['title'] . ': ';

			if (!$activity['activity']) {
				echo "Not in the last hour.";
			} else {
				$seconds = time() - $activity['activity'];
				if ($seconds === 0) {
					echo __("now");
				} elseif ($seconds < 120) {
					echo sprintf(_n('%s second ago', '%s seconds ago', $seconds), $seconds);
				} else {
					echo sprintf(__('%s ago'), human_time_diff($activity['activity'], time()));
				}
			}

			if ($activity['description']) {
				echo " <small><em>({$activity['description']})</em></small>";
			}

			echo '<br>';
		}
		echo '</p>';

		do_action('podlove_jobs_tools_end');
	}
}
