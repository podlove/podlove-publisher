<?php
namespace Podlove\Modules\ImportExport;

class Import_Export extends \Podlove\Modules\Base {

	protected $module_name = 'Import &amp; Export';
	protected $module_description = 'Import &amp; export podlove data for backup or migration to another WordPress instance.';
	protected $module_group = 'system';

	public function load() {

		add_action( 'wp_ajax_podlove-export-tracking', array($this, 'export_tracking') );
		add_action( 'wp_ajax_podlove-export-tracking-status', array($this, 'export_tracking_status') );
		
		// hook into export feature
		add_action('init', function() {

			if (!is_admin())
				return;

			if (isset($_GET['podlove_export']) && $_GET['podlove_export']) {
				$exporter = new Export\Podcast;
				$exporter->download();
				exit;
			}

			if (isset($_GET['podlove_export_tracking']) && $_GET['podlove_export_tracking']) {
				
				header( 'Content-Type: application/octet-stream' );
				header( 'Content-Description: File Transfer' );
				header( 'Content-Disposition: attachment; filename=tracking.csv.gz' );
				header( 'Cache-control: private' );
				header( 'Expires: -1' );

				readfile(\Podlove\Modules\ImportExport\Import_Export::get_tracking_export_file_path());
				exit;
			}

		});

		add_action( 'admin_menu', array( $this, 'register_menu' ), 250 );
	}

	public function register_menu() {
		new Settings\Settings( \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE );
	}

	public static function get_tracking_export_file_path() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "tracking.tmp";
	}

	public function export_tracking() {
		global $wpdb;

		// only one export at a time
		if (get_option('podlove_tracking_export_all') !== false)
			return;

		update_option('podlove_tracking_export_all', $wpdb->get_var("SELECT COUNT(*) FROM " . \Podlove\Model\DownloadIntent::table_name()));
		update_option('podlove_tracking_export_progress', 0);

		$rowsPerQuery = 1000;
		$lastId = 0;
		$page = 0;

		$fp = gzopen(self::get_tracking_export_file_path(), 'w');

		do {
			// Keeping track of the $lastId is (roughly) a bajillion times faster than paging via LIMIT.
			$sql = "
				SELECT
					*
				FROM
					" . \Podlove\Model\DownloadIntent::table_name() . "
					WHERE id > " . (int) $lastId . "
				LIMIT 0, $rowsPerQuery";
			$rows = $wpdb->get_results($sql, ARRAY_A);
			foreach ($rows as $row) {
				gzwrite($fp, implode(",", $row) . "\n");
			}

			$lastId = $row['id'];
			$page++;

			update_option('podlove_tracking_export_progress', $page*$rowsPerQuery);
		} while (count($rows) > 0);

		gzclose($fp);

		delete_option('podlove_tracking_export_all');
		delete_option('podlove_tracking_export_progress');
		exit;
	}

	public function export_tracking_status() {
		echo json_encode(array(
			'all'      => get_option('podlove_tracking_export_all'),
			'progress' => get_option('podlove_tracking_export_progress')
		));
		exit;
	}

}