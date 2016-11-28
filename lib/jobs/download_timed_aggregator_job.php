<?php 
namespace Podlove\Jobs;

use Podlove\Model;

/**
 * Aggregates downloads by time
 */
class DownloadTimedAggregatorJob {
	use JobTrait;

	public static function title() {
		return __('Download Aggregation', 'podlove-podcasting-plugin-for-wordpress');
	}

	public static function description() {
		return __('Recalculates sums and totals for episode downloads.', 'podlove-podcasting-plugin-for-wordpress');
	}

	public function setup() {
		$this->hooks['init'] = [$this, 'setup_state'];
	}

	public static function defaults() {
		return [
			'force' => false
		];
	}

	public function setup_state() {
		$episodes = Model\Podcast::get()->episodes();
		$episode_ids = array_map(function($e) { return $e->id; }, $episodes);

		$this->job->state = [
			'episode_ids'    => $episode_ids, // reduced to empty array during job
			'total_episodes' => count($episode_ids) // immutable
		];
	}

	public function get_total_steps() {
		return $this->job->state['total_episodes'];
	}

	protected function do_step() {
		
		$state = $this->job->state;
		$episode_id = array_pop($state['episode_ids']);
		$this->job->state = $state;
		
		$episode = Model\Episode::find_by_id($episode_id);

		if (!$episode)
			return 1;

		$max_hsr = Model\DownloadIntentClean::actual_episode_age_in_hours($episode_id);
		$groupings = self::groupings();

		foreach ($groupings as $key => $hours) {
			if ($this->should_calculate_grouping($episode_id, $key, $hours, $max_hsr)) {
				self::calculate_single_aggregation($episode, $key, $hours);
			}
		}

		return 1;
	}

	private function should_calculate_grouping($episode_id, $group_key, $group_hours, $max_hsr)
	{
		// skip fields that cannot be calculated yet
		if ($max_hsr <= $group_hours)
			return false;
		
		// always calculate if enforced
		if ($this->job->args['force'])
			return true;

		// always calculate totals
		if ($group_key === 'total')
			return true;

		// skip if field is already calculated
		return !((bool) get_post_meta($episode_id, '_podlove_downloads_' . $group_key, true));		
	}

	private function calculate_single_aggregation($episode, $grouping_key, $grouping_hours)
	{
		global $wpdb;

		$sql = 'SELECT
			  COUNT(*)
			FROM ' . Model\DownloadIntentClean::table_name() . ' di
			INNER JOIN ' . Model\MediaFile::table_name() . ' mf ON mf.id = di.media_file_id
			INNER JOIN ' . Model\Episode::table_name() . ' e ON mf.episode_id = e.id
			WHERE e.id = %d';
		$sql_params = [$episode->id];

		if ($grouping_hours && $grouping_hours > 0) {
			$sql .= ' AND hours_since_release <= %d';
			$sql_params[] = $grouping_hours;
		}

		$downloads = $wpdb->get_var($wpdb->prepare($sql, $sql_params));

		if ($downloads && is_numeric($downloads)) {
			update_post_meta($episode->post_id, '_podlove_downloads_' . $grouping_key, $downloads);
		}
	}

	public static function current_time_group($hours_since_release)
	{
		$groupings = self::groupings();
		$group_keys = array_reverse(array_keys($groupings));

		for ($i = 0; $i < count($group_keys); $i++) {
			
			$current_key = $group_keys[$i];
			// $next_key    = isset($group_keys[$i + 1]) ? $group_keys[$i + 1] : NULL;
			// $prev_key    = isset($group_keys[$i - 1]) ? $group_keys[$i - 1] : NULL;

			if ($hours_since_release <= $groupings[$current_key]) {
				return $current_key;
			}
		}

		return NULL;
	}

	public static function groupings()
	{
		return  [
			'total' => null,
			'1y' => 24 * 7 * 52,
			'4w' => 24 * 7 * 4,
			'3w' => 24 * 7 * 3,
			'2w' => 24 * 7 * 2,
			'1w' => 24 * 7,
			'6d' => 24 * 6,
			'5d' => 24 * 5,
			'4d' => 24 * 4,
			'3d' => 24 * 3,
			'2d' => 24 * 2,
			'1d' => 24
		];
	}

}
