<?php 
namespace Podlove\Jobs;

use Podlove\Model;

class DownloadIntentCleanupJob {
	use JobTrait;

	public function __construct($args = []) {

		$defaults = [
			'intents_total' => self::get_max_intent_id(),
			'intents_per_step' => 100000
		];

		$this->args = wp_parse_args($args, $defaults);
		$this->hooks['finished'] = [__CLASS__, 'purge_cache'];
		$this->hooks['init'] = [__CLASS__, 'delete_clean_intents'];

		$this->state = ['previous_id' => 0];
	}

	public static function get_max_intent_id()
	{
		global $wpdb;
		return $wpdb->get_var('SELECT MAX(id) FROM `' . Model\DownloadIntent::table_name() . '`');
	}

	public function get_total_steps() {
		return $this->args['intents_total'];
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

		$from = $this->state['previous_id'];
		$to   = $this->state['previous_id'] + $this->args['intents_per_step'];

		$wpdb->query(
			$wpdb->prepare($sql, $from, $to)
		);

		$this->state['previous_id'] = $to;

		return $this->args['intents_per_step'];
	}

	public static function purge_cache() {
		\Podlove\Cache\TemplateCache::get_instance()->setup_purge();
	}

	public static function delete_clean_intents() {
		Model\DownloadIntentClean::delete_all();
	}
}
