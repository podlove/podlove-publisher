<?php 
namespace Podlove\Jobs;

use Podlove\Model;

class RequestIdRehashJob {
	use JobTrait;

	public static function title() {
		return __('Rehash Tracking Request IDs', 'podlove-podcasting-plugin-for-wordpress');
	}

	public static function description() {
		return __('Improve request id anonymity for DSVGO.', 'podlove-podcasting-plugin-for-wordpress');
	}

	private static function downloads_table_name() {
		return \Podlove\Model\DownloadIntent::table_name();
	}

	public static function defaults() {
		return [
			'intents_total' => podlove_rehash_total_remaining(self::downloads_table_name()),
			'ids_per_step' => 1000
		];
	}

	public function get_total_steps() {
		return $this->job->args['intents_total'];
	}	

	protected function do_step() {
		global $wpdb;

		$request_ids = podlove_rehash_fetch_some_request_ids(
			self::downloads_table_name(), 
			$this->job->args['ids_per_step']
		);

		foreach ($request_ids as $request_id) {
			podlove_rehash_replace_request_id(self::downloads_table_name(), $request_id);
		}

		return $this->job->args['ids_per_step'];
	}
}
