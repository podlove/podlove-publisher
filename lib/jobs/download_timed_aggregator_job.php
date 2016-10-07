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
		return __('Recalculates sums for episode downloads in Analytics overview page.', 'podlove-podcasting-plugin-for-wordpress');
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

		$max_hsr = Model\DownloadIntentClean::actual_episode_age_in_hours($episode->id);
		$groupings = self::groupings();

		foreach ($groupings as $key => $hours) {
			// skip already calculated fields IF override is not forced
			if (!$this->job->args['force'] && get_post_meta($episode->post_id, '_podlove_downloads_' . $key, true) != '')
				continue;

			// skip fields that cannot be calculated yet
			if ($max_hsr <= $hours)
				continue;

			self::calculate_single_aggregation($episode, $key, $hours);
		}

		return 1;
	}

	private function calculate_single_aggregation($episode, $grouping_key, $grouping_hours)
	{
		global $wpdb;

		$sql = $wpdb->prepare(
			'SELECT
			  COUNT(*)
			FROM ' . Model\DownloadIntentClean::table_name() . ' di
			INNER JOIN ' . Model\MediaFile::table_name() . ' mf ON mf.id = di.media_file_id
			INNER JOIN ' . Model\Episode::table_name() . ' e ON mf.episode_id = e.id
			WHERE e.id = %d AND hours_since_release <= %d',
			$episode->id, $grouping_hours
		);
		$downloads = $wpdb->get_var($sql);

		if ($downloads && is_numeric($downloads)) {
			update_post_meta($episode->post_id, '_podlove_downloads_' . $grouping_key, $downloads);
		}
	}

	public static function groupings()
	{
		return  [
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
