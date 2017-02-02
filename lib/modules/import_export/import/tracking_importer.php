<?php
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Model;
use Podlove\Jobs\CronJobRunner;

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

			CronJobRunner::create_job('\Podlove\Modules\ImportExport\Import\TrackingImporterJob');
		} else {
			echo '<div class="error"><p>' . $file['error'] . '</p></div>';
		}

		add_action('admin_notices', [__CLASS__, 'print_notice']);
	}

	public function __construct($file) {
		$this->file = $file;
	}

	public static function print_notice()
	{
		?>
		<div class="updated">
			<p>
				<strong>
					<?php echo __('Tracking Import Started', 'podlove-podcasting-plugin-for-wordpress') ?>
				</strong>
			</p>
			<p>
				<?php echo __('See "Background Jobs" section below for progress.', 'podlove-podcasting-plugin-for-wordpress') ?>
			</p>
		</div>
		<?php
	}
}
