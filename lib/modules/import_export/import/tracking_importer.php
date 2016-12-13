<?php
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Model;

class TrackingImporter {
	
	// path to import file
	private $file;

	public static function init()
	{
		if (!is_admin())
			return;
		
		if (!isset($_FILES['podlove_import_tracking']))
			return;

		set_time_limit(10 * MINUTE_IN_SECONDS);

		// allow xml+gz uploads
		add_filter('upload_mimes', function ($mimes) {
		    return array_merge($mimes, array(
		    	'xml' => 'application/xml',
		    	'gz|gzip' => 'application/x-gzip'
		    ));
		});

		require_once ABSPATH . '/wp-admin/includes/file.php';
		 
		$file = wp_handle_upload($_FILES['podlove_import_tracking'], array('test_form' => false));
		if ($file && (!isset($file['error']) || !$file['error'])) {
			update_option('podlove_import_tracking_file', $file['file']);
			if (!($file = get_option('podlove_import_tracking_file')))
				return;

			$importer = new \Podlove\Modules\ImportExport\Import\TrackingImporter($file);
			$importer->import();
		} else {
			echo '<div class="error"><p>' . $file['error'] . '</p></div>';
		}
	}

	public function __construct($file) {
		$this->file = $file;
	}

	public function import() {
		// It might not look like it, but it is actually compatible to 
		// uncompressed files.
		$gzFileHandler = gzopen($this->file, 'r');

		Model\DownloadIntent::delete_all();
		Model\DownloadIntentClean::delete_all();
		
		$batchSize = 1000;
		$batch = array();

		while (!gzeof($gzFileHandler)) {
			$line = gzgets($gzFileHandler);

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

			if (count($batch) >= $batchSize) {
				self::save_batch_to_db($batch);
				$batch = [];
			}
		}

		gzclose($gzFileHandler);

		// save last batch to db
		self::save_batch_to_db($batch);

		\Podlove\Analytics\DownloadIntentCleanup::cleanup_download_intents();
		\Podlove\Cache\TemplateCache::get_instance()->setup_purge();

		wp_redirect(admin_url('admin.php?page=podlove_tools_settings_handle&status=success'));
		exit;
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
				return "(" . implode(",", array_map(function($x){
					return '"' . $x . '"';
				}, $row)) . ")";
			}, $batch));
			$sql = sprintf($sqlTemplate, $inserts);
			$wpdb->query($sql);
		}		
	}
}
