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

		?>
			<div id="podlove-tools-dashboard"><jobs-dashboard></jobs-dashboard></div>		
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
