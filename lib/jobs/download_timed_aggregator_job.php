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

	public static function mode($args)
	{
		if (isset($args['force']) && $args['force']) {
			return __('From Scratch', 'podlove-podcasting-plugin-for-wordpress');
		} else {
			return __('Partial', 'podlove-podcasting-plugin-for-wordpress');
		}
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

	/**
	 * Get "in progress" time group.
	 * 
	 * For the first 24h after a release "1d" is returned, followed by "2d" etc.
	 * The next segment is only returned once the sum for the current segment
	 * has been calculated.
	 * 
	 * @param  array $item
	 * @return NULL|string
	 */
	public static function current_time_group($item)
	{
		$groupings = self::groupings();
		$hidden_groups = self::get_hidden_groups();

		$group_keys = array_reverse(array_keys($groupings));

		// return first column without downloads
		foreach ($group_keys as $key) {

			// ignore 'total' column
			if ($key == 'total')
				continue;

			if (in_array($key, $hidden_groups))
				continue;

			// ignore columns with calculated values
			if (isset($item[$key]) && $item[$key] > 0)
				continue;

			// ignore old segments without tracking
			$two_days_ago     = time() - DAY_IN_SECONDS * 2;
			$segment_end_time = strtotime($item['post_date']) + $groupings[$key] * HOUR_IN_SECONDS;

			if ($segment_end_time < $two_days_ago)
				continue;

			return $key;
		}

		return NULL;
	}

	public static function get_hidden_groups_key()
	{
		return 'managepodlove_page_podlove_analyticscolumnshidden';
	}

	public static function get_hidden_groups()
	{
		return get_user_meta(get_current_user_id(), self::get_hidden_groups_key(), true);
	}

	public static function groupings()
	{
		return  [
			'total' => null,
			'3y' => 24 * 7 * 52 * 3,
			'2y' => 24 * 7 * 52 * 2,
			'1y' => 24 * 7 * 52,
			'3q' => 24 * 7 * 13 * 3,
			'2q' => 24 * 7 * 13 * 2,
			'1q' => 24 * 7 * 13,
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
