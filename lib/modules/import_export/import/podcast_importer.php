<?php
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Model;
use Podlove\Modules\ImportExport\Export\PodcastExporter;
use Podlove\Jobs\CronJobRunner;
use Podlove\Model\Job;

class PodcastImporter {

	// path to import file
	private $file;

	// SimpleXML document of import file
	private $xml;

	public static function init()
	{
		add_action( 'wp_ajax_podlove-import-status', [__CLASS__, 'ajax_render_status']);

		if (!is_admin())
			return;

		if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'podlove_tools_settings_handle') {
			add_action('admin_notices', [__CLASS__, 'render_import_progress']);
		}

		if (!isset($_FILES['podlove_import']))
			return;

		// allow xml+gz uploads
		add_filter('upload_mimes', function ($mimes) {
		    return array_merge($mimes, array(
		    	'xml' => 'application/xml',
		    	'gz|gzip' => 'application/x-gzip'
		    ));
		});

		require_once ABSPATH . '/wp-admin/includes/file.php';
		 
		$file = wp_handle_upload($_FILES['podlove_import'], array('test_form' => false));
		
		update_option('podlove_import_file', $file['file']);
		if (!($file = get_option('podlove_import_file')))
			return;

		// delete all jobs before starting import
		Model\Job::delete_all();

		foreach (self::get_import_job_classes() as $job) {
			CronJobRunner::create_job($job);
		}

		$redirect_url = 'admin.php?page=podlove_tools_settings_handle';
		wp_redirect(admin_url($redirect_url));
		exit;

	}

	public static function get_import_job_classes()
	{
		return [
			'\Podlove\Modules\ImportExport\Import\PodcastImportEpisodesJob',
			'\Podlove\Modules\ImportExport\Import\PodcastImportOptionsJob',
			'\Podlove\Modules\ImportExport\Import\PodcastImportAssetsJob',
			'\Podlove\Modules\ImportExport\Import\PodcastImportFeedsJob',
			'\Podlove\Modules\ImportExport\Import\PodcastImportFiletypesJob',
			'\Podlove\Modules\ImportExport\Import\PodcastImportMediafilesJob',
			'\Podlove\Modules\ImportExport\Import\PodcastImportTrackingAreaJob',
			'\Podlove\Modules\ImportExport\Import\PodcastImportTrackingAreaNameJob',
			'\Podlove\Modules\ImportExport\Import\PodcastImportUserAgentsJob',
			'\Podlove\Modules\ImportExport\Import\PodcastImportTemplatesJob'
		];
	}

	public static function ajax_render_status()
	{
		self::render_import_progress_jobs();
		exit;
	}

	public static function render_import_progress()
	{
		$jobs = self::get_import_job_classes();
		$jobs = apply_filters('podlove_import_jobs', $jobs);

		$any_unfinished = array_reduce($jobs, function($any, $job) {

			if ($any) {
				return $any;
			} else {
				return !!Job::find_one_recent_unfinished_job($job);
			}

		}, false);

		if (!$any_unfinished)
			return;

		?>
		<div class="updated" id="podlove-import-status">
			<p>
				<strong>Import is running, please wait.</strong>
			</p>
			<?php self::render_import_progress_jobs(); ?>
		</div>
		<?php
	}

	public static function render_import_progress_jobs()
	{
		$jobs = self::get_import_job_classes();
		$jobs = apply_filters('podlove_import_jobs', $jobs);
		?>
		<ul>
			<?php foreach ($jobs as $jobClass): ?>
				<?php $job = Job::find_one_recent_unfinished_job($jobClass) ?>
				<li>
					<?php echo $jobClass::title() ?>
					<?php if ($job): ?>
						<em>
							<?php 
							echo sprintf(
								"%d%%", 
								100 * ($job->steps_progress / $job->steps_total))
							?>
						</em>
						<?php if ($job->steps_progress > 0): ?>
							<i class="clickable podlove-icon-spinner rotate"></i>
						<?php endif ?>
					<?php else: ?>
						<i class="clickable podlove-icon-ok"></i>
					<?php endif ?>
				</li>
			<?php endforeach ?>
		</ul>		
		<?php
	}
}
