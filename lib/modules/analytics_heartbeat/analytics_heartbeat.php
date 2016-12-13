<?php 
namespace Podlove\Modules\AnalyticsHeartbeat;

use \Podlove\Modules\AnalyticsHeartbeat\Model\Heartbeat;

class Analytics_Heartbeat extends \Podlove\Modules\Base {

	protected $module_name = 'Analytics Heartbeat';
	protected $module_description = 'Keeps track of when Analytics are active or inactive.';
	protected $module_group = 'system';

	public static function is_core() {
		return true;
	}

	public function load() {
		// add_action('podlove_module_was_activated_analytics_heartbeat', [$this, 'was_activated']);
		add_action('podlove_analytics_heartbeat', [$this, 'check_analytics_status']);
		$this->schedule_crons();
	}

	// public function was_activated($module_name) {
	// }

	public function schedule_crons() {
		if (!wp_next_scheduled('podlove_analytics_heartbeat'))
			wp_schedule_event(time(), 'hourly', 'podlove_analytics_heartbeat');
	}

	public function check_analytics_status() {
		
		Heartbeat::build();

		$current_status = \Podlove\get_setting('tracking', 'mode');
		$last_beat = Heartbeat::last();

		if (!$last_beat || $current_status != $last_beat->status) {
			$heartbeat = new Heartbeat;
			$heartbeat->status_start = date("Y-m-d H:i:s");
			$heartbeat->status_end   = date("Y-m-d H:i:s");
			$heartbeat->status = $current_status;
			$heartbeat->beats = 1;
			$heartbeat->save();
		} else {
			$last_beat->beats++;
			$last_beat->status_end = date("Y-m-d H:i:s");
			$last_beat->save();
		}

	}
}
