<?php 
namespace Podlove\Jobs;

use Podlove\Model;

/**
 * Aggregates all downloads by episode
 */
class DownloadTotalsAggregatorJob {
	use JobTrait;

	public function setup() {
		$this->hooks['init'] = [$this, 'setup_state'];
	}

	public static function defaults() {
		return [];
	}

	public function setup_state() {
		$episodes = Model\Podcast::get()->episodes();
		$episode_ids = array_map(function($e) { return $e->id; }, $episodes);

		$this->state = [
			'episode_ids'    => $episode_ids, // reduced to empty array during job
			'total_episodes' => count($episode_ids) // immutable
		];
	}

	public function get_total_steps() {
		return $this->state['total_episodes'];
	}

	protected function do_step() {
		
		$episode_id = array_pop($this->state['episode_ids']);
		$episode = Model\Episode::find_by_id($episode_id);

		if (!$episode)
			return 1;

		$total = Model\DownloadIntentClean::total_by_episode_id($episode->id);
		if ($total) {
			update_post_meta($episode->post_id, '_podlove_downloads_total', $total);
		}

		return 1;
	}
}
