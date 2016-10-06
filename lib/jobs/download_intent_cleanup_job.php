<?php 
namespace Podlove\Jobs;

use Podlove\Model;

class DownloadIntentCleanupJob {
	use JobTrait;

	public static function title() {
		return __('Download Intent Cleanup', 'podlove-podcasting-plugin-for-wordpress');
	}

	public static function description() {
		return __('Only cleaned download intents are available for analytics reports. Cleaning involves deduplication and removal of requests made by bots.', 'podlove-podcasting-plugin-for-wordpress');
	}

	public function setup() {
		
		$this->hooks['finished'] = [__CLASS__, 'purge_cache'];

		if ($this->job->args['delete_all']) {
			$this->hooks['init'] = [__CLASS__, 'delete_clean_intents'];
			$this->job->state = ['previous_id' => 0];
		} else {
			$this->job->state = ['previous_id' => self::get_max_clean_intent_id()];
		}

		$this->job->steps_progress = $this->job->state['previous_id'];
	}

	public static function defaults() {
		return [
			'intents_total' => self::get_max_intent_id(),
			'intents_per_step' => 100000,
			'delete_all' => true // delete all clean intents before starting
		];
	}

	public static function get_max_intent_id() {
		global $wpdb;
		
		$id = $wpdb->get_var('SELECT MAX(id) FROM `' . Model\DownloadIntent::table_name() . '`');

		return $id ? (int) $id : 0;
	}

	public static function get_max_clean_intent_id() {
		global $wpdb;
		
		$id = $wpdb->get_var('SELECT MAX(id) FROM `' . Model\DownloadIntentClean::table_name() . '`');

		return $id ? (int) $id : 0;
	}

	public function get_total_steps() {
		return $this->job->args['intents_total'];
	}

	protected function do_step() {
		global $wpdb;

		$sql = "INSERT INTO `" . Model\DownloadIntentClean::table_name() . "` (`id`, `user_agent_id`, `media_file_id`, `request_id`, `accessed_at`, `source`, `context`, `geo_area_id`, `lat`, `lng`, `httprange`, `hours_since_release`)
		SELECT
			di.id, `user_agent_id`, `media_file_id`, `request_id`, `accessed_at`, `source`, `context`, `geo_area_id`, `lat`, `lng`, `httprange`,
			TIMESTAMPDIFF(HOUR, p.post_date_gmt, accessed_at)
		FROM
			`" . Model\DownloadIntent::table_name() . "` di
			INNER JOIN " . Model\MediaFile::table_name() . " mf ON mf.id = di.media_file_id -- filter dead intents
			INNER JOIN " . Model\Episode::table_name() . " e ON episode_id = e.id
			INNER JOIN $wpdb->posts p ON e.post_id = p.ID
		WHERE
			di.accessed_at > p.post_date_gmt -- ignore pre-release intents
			AND user_agent_id NOT IN (SELECT id FROM `" . Model\UserAgent::table_name() . "` WHERE bot) -- filter out bots
			AND di.id > %d AND di.id <= %d
			AND (di.httprange != 'bytes=0-1' OR httprange IS NULL) -- filter out 1 byte requests; allow requests with empty httprange
		GROUP BY media_file_id, request_id, DATE_FORMAT(accessed_at, '%%Y-%%m-%%d %%H') -- deduplication
		";

		$from = $this->job->state['previous_id'];
		$to   = $this->job->state['previous_id'] + $this->job->args['intents_per_step'];

		$wpdb->query(
			$wpdb->prepare($sql, $from, $to)
		);

		$this->job->update_state('previous_id', $to);

		return $this->job->args['intents_per_step'];
	}

	public static function purge_cache() {
		\Podlove\Cache\TemplateCache::get_instance()->setup_purge();
	}

	public static function delete_clean_intents() {
		Model\DownloadIntentClean::delete_all();
	}
}
