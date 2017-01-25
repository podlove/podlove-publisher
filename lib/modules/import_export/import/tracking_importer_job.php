<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;
use Podlove\Jobs\CronJobRunner;
use Podlove\Model;

class TrackingImporterJob {
	use JobTrait;

	public static function title() { return 'Podcast Tracking Importer'; }

	public static function description() { return 'Imports Podcast Analytics'; }

	public function setup() {
		$this->hooks['init']     = [$this, 'init_job'];
		$this->hooks['finished'] = [$this, 'recalculate_analytics'];
	}

	public function recalculate_analytics()
	{
		$jobs = [
			'\Podlove\Jobs\DownloadIntentCleanupJob'    => ['delete_all' => true],
			'\Podlove\Jobs\DownloadTimedAggregatorJob'  => ['force' => true]
		];

		foreach ($jobs as $job => $args) {
			CronJobRunner::create_job($job, $args);
		}		
	}

	public function init_job()
	{
		Model\DownloadIntent::delete_all();
		Model\DownloadIntentClean::delete_all();

		$this->job->state = [
			'offset' => 0
		];
	}

	public function get_total_steps() {
		return $this->get_lines_in_file();
	}

	protected function do_step() {
		$fp = gzopen($this->get_file(), 'r');
		$batchSize = 1000;
		$batch = [];

		$offset = (int) $this->job->state['offset'];

		if ($offset) {
			gzseek($fp, $offset, SEEK_SET);
		}

		while (!gzeof($fp) && count($batch) < $batchSize) {
			$line = gzgets($fp);

			list(
				$id,
				$user_agent_id,
				$media_file_id,
				$request_id,
				$accessed_at,
				$source,
				$context,
				$geo_area_id,
				$lat,
				$lng,
				$httprange
			) = array_map(function ($value) {
				return trim($value);
			}, explode(",", $line));

			$batch[] = array(
				$user_agent_id,
				$media_file_id,
				$request_id,
				$accessed_at,
				$source,
				$context,
				$geo_area_id,
				$lat,
				$lng,
				$httprange
			);
		}

		$offset = gztell($fp);

		gzclose($fp);

		self::save_batch_to_db($batch);

		$state = $this->job->state;
		$state['offset'] = $offset;
		$this->job->state = $state;

		return count($batch);
	}

	private static function save_batch_to_db($batch) {
		global $wpdb;

		$sqlTemplate = "
			INSERT INTO
				" . Model\DownloadIntent::table_name() . " 
			( `user_agent_id`, `media_file_id`, `request_id`, `accessed_at`, `source`, `context`, `geo_area_id`, `lat`, `lng`, `httprange`) 
			VALUES %s";

		if (count($batch)) {
			$inserts = implode(",", array_map(function($row) {
				return "(" . implode(",", array_map(function($x) {
					return '"' . $x . '"';
				}, $row)) . ")";
			}, $batch));
			$sql = sprintf($sqlTemplate, $inserts);
			$wpdb->query($sql);
		}
	}

	private function get_file()
	{
		return get_option('podlove_import_tracking_file');
	}

	private function get_lines_in_file()
	{
		$linecount = 0;
		$handle = gzopen($this->get_file(), "r");
		while (!gzeof($handle)) {
			$line = gzgets($handle);
			$linecount++;
		}

		gzclose($handle);

		error_log(print_r("linecount: $linecount (" . $this->get_file() . ")", true));

		return $linecount;		
	}
}
