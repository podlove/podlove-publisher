<?php
namespace Podlove\Modules\ImportExport;

class Import_Export extends \Podlove\Modules\Base {

	protected $module_name = 'Import &amp; Export';
	protected $module_description = 'Import &amp; export podlove data for backup or migration to another WordPress instance.';
	protected $module_group = 'system';

	public function load() {
		
		add_action('admin_init', function() {
			Export\PodcastExporter::init();
			Import\PodcastImporter::init();
			Export\TrackingExporter::init();
			Import\TrackingImporter::init();
			$this->register_tools();
		});

		add_action('admin_notices', function() {

			if (!isset($_GET['page']))
				return false;

			if ($_GET['page'] != 'podlove_tools_settings_handle')
				return false;

			if (!isset($_GET['status']))
				return false;

			?>
			<div class="updated">
				<p>
					<?php
					switch ($_GET['status']) {
						case 'success':
							echo __('Import successful. Happy podcasting!');
							break;
						case 'version-warning':
							echo __('Heads up: Your export file was exported from a Publisher with a different version. If possible, both Publisher versions should be identical. However, that might not be a problem. Happy podcasting!');
							break;
					}
					?>
				</p>
			</div>
			<?php
		});
	}

	function register_tools() {
		\Podlove\add_tools_section(
			'import-export',
			__('Import & Export', 'podlove-podcasting-plugin-for-wordpress'),
			function() {
				if (defined('SAVEQUERIES') && SAVEQUERIES) {
					?>
					<div class="error">
						<p>
							<b><?php echo __('Heads up!', 'podlove-podcasting-plugin-for-wordpress') ?></b>
							<?php echo __('The WordPress debug option <code>SAVEQUERIES</code> is active. This might lead to memory issues when exporting or importing tracking data.<br>It is probably defined in <code>wp-config.php</code>. Please turn it off before using the export tool.', 'podlove-podcasting-plugin-for-wordpress') ?>
						</p>
					</div>
					<?php
				}
			}
		);

		\Podlove\add_tools_field(
			'export-podcast',
			__('Podcast Export', 'podlove-podcasting-plugin-for-wordpress'),
			[$this, 'tools_podcast_export'],
			'import-export'
		);

		\Podlove\add_tools_field(
			'export-tracking',
			__('Tracking Export', 'podlove-podcasting-plugin-for-wordpress'),
			[$this, 'tools_tracking_export'],
			'import-export'
		);

		\Podlove\add_tools_field(
			'import-podcast',
			__('Podcast Import', 'podlove-podcasting-plugin-for-wordpress'),
			[$this, 'tools_podcast_import'],
			'import-export'
		);

		\Podlove\add_tools_field(
			'import-tracking',
			__('Tracking Import', 'podlove-podcasting-plugin-for-wordpress'),
			[$this, 'tools_tracking_import'],
			'import-export'
		);
	}

	public function tools_podcast_export() {
		echo sprintf(
			__('This export complements the existing %sWordPress export tool%s. It contains all relevant podcast data to enable you to move from this WordPress instance to another. Step by step:', 'podlove-podcasting-plugin-for-wordpress'),
			'<a href="' . admin_url('export.php') . '">', '</a>'
		);
		?>
		<ol>
			<li>
				<?php echo sprintf(
					__('Go to the %sWordPress export tool%s and export all data.', 'podlove-podcasting-plugin-for-wordpress'),
					'<a href="' . admin_url('export.php') . '">', '</a>' 
				); ?>
			</li>
			<li><?php echo __('Import this file to your new WordPress instance.', 'podlove-podcasting-plugin-for-wordpress') ?></li>
			<li><?php echo __('Use the button below to export the podcast data file.', 'podlove-podcasting-plugin-for-wordpress') ?></li>
			<li><?php echo __('In your new WordPress instance, import that file.', 'podlove-podcasting-plugin-for-wordpress') ?></li>
		</ol>

		<a href="?podlove_export=1" class="button"><?php echo __('Export Podcast Data', 'podlove-podcasting-plugin-for-wordpress') ?></a>
		<?php
	}

	public function tools_tracking_export() {
		?>
		<p>
			<?php echo __('Im- and export of tracking data is a separate task. After you have completed the steps above, you can ex- and import tracking data.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</p>
		<p>
			<button id="podlove_tracking_export" class="button"><?php echo __('Export Tracking Data', 'podlove-podcasting-plugin-for-wordpress') ?></button>
			<span id="podlove_tracking_export_status_wrapper">
				<?php echo __('Export', 'podlove-podcasting-plugin-for-wordpress') ?>: <span id="podlove_tracking_export_status">starting ...</span>
			</span>
		</p>

		<style type="text/css">
		#podlove_tracking_export_status_wrapper {
			display: none;
		}
		</style>

		<script type="text/javascript">
		(function($) {

			var timeoutID = null,
				isExporting = false;

			var podlove_check_export_status = function() {

				if (timeoutID) {
					window.clearTimeout(timeoutID);
				}

				$.ajax({
					url: ajaxurl,
					data: {action: 'podlove-export-tracking-status'},
					dataType: 'json',
					success: function(result) {
						if (result.all && result.progress) {
							$("#podlove_tracking_export").attr('disabled', 'disabled');
							$("#podlove_tracking_export_status").html((Math.round(1000.0 * (result.progress / result.all))/10.0) + "%");
							$("#podlove_tracking_export_status_wrapper").show();

							isExporting = true;
							timeoutID = window.setTimeout(podlove_check_export_status, 1000);
						} else {
							$("#podlove_tracking_export").attr('disabled', false);
							if (isExporting) {
								$("#podlove_tracking_export_status_wrapper").hide();
								window.location = window.location + "&podlove_export_tracking=1";
							}
						}
					}
				});

			};

			$("#podlove_tracking_export").on("click", function() {

				$("#podlove_tracking_export").attr('disabled', 'disabled');
				$("#podlove_tracking_export_status_wrapper").show();

				$.ajax({
					url: ajaxurl,
					data: {action: 'podlove-export-tracking'},
					dataType: 'json',
					success: function(result) {
						console.log("tracking export finished");
					}
				});

				window.setTimeout(podlove_check_export_status, 2000);
			});

			// start immediately, in case the user refreshes the page
			podlove_check_export_status();
		}(jQuery));
		</script>		
		<?php
	}

	public function tools_podcast_import() {
		?>
		<p>
			<?php echo __('Use this import on <strong>fresh installs only</strong>! Otherwise you may lose data. In any case, you should have backups.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</p>

		<form method="POST" enctype="multipart/form-data">
			(<span><?php echo self::get_maximum_upload_size_text() ?></span>)
			<input type="file" name="podlove_import"/> 
			<input type="submit" value="<?php echo __('Import Podcast Data', 'podlove-podcasting-plugin-for-wordpress') ?>" class="button" />
		</form>		
		<?php
	}

	public function tools_tracking_import() {
		?>
		<form method="POST" enctype="multipart/form-data">
			(<span><?php echo self::get_maximum_upload_size_text() ?></span>)
			<input type="file" name="podlove_import_tracking"/>
			<input type="submit" value="<?php echo __('Import Tracking Data', 'podlove-podcasting-plugin-for-wordpress') ?>" class="button" />
		</form>		
		<?php
	}

	public static function get_maximum_upload_size_text() {
		// this is exactly the same way it is done in wp_import_upload_form()
		$bytes = apply_filters( 'import_upload_size_limit', \wp_max_upload_size() );
		$size = \size_format( $bytes );
		return sprintf( __('Maximum size: %s' ), $size );
	}

}
