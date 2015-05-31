<?php
namespace Podlove;

/**
 * Remove all scheduled cron jobs with this name
 */
function unschedule_events($hook)
{
	$crons = get_option('cron');

	foreach ($crons as $time => $cron) {
		if (isset($cron[$hook])) {
			unset($crons[$time][$hook]);
		}
	}

	update_option('cron', $crons);
}