<?php
namespace Podlove\Modules\ImportExport\Settings\Tab;

class Export extends \Podlove\Settings\Expert\Tab {

	public function init() {
		$this->page_type = 'custom';
	}

	public function page() {

		// whenever the page is loaded, reset tracking export progress
		delete_option('podlove_tracking_export_all');
		delete_option('podlove_tracking_export_progress');

		do_action('podlove_imexport_settings_head');
		?>

		<h3><?php _e('Podcast Export', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>

		<table class="form-table">
			<tr>
				<td colspan="2">
					<?php echo sprintf(
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
						<li><?php _e('Import this file to your new WordPress instance.', 'podlove-podcasting-plugin-for-wordpress'); ?></li>
						<li><?php _e('Use the button below to export the podcast data file.', 'podlove-podcasting-plugin-for-wordpress'); ?></li>
						<li><?php _e('In your new WordPress instance, import that file.', 'podlove-podcasting-plugin-for-wordpress'); ?></li>
					</ol>

					<a href="?podlove_export=1" class="button-primary"><?php _e('Export Podcast Data', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
				</td>
			</tr>
		</table>

		<h3><?php _e('Tracking Export', 'podlove-podcasting-plugin-for-wordpress') ?></h3>

		<table class="form-table">
			<tr>
				<td colspan="2">
					<p>
						<?php _e('Im- and export of tracking data is a separate task. After you have completed the steps above, you can ex- and import tracking data.', 'podlove-podcasting-plugin-for-wordpress'); ?>
					</p>
					<p>
						<button id="podlove_tracking_export" class="button-primary"><?php _e('Export Tracking Data', 'podlove-podcasting-plugin-for-wordpress') ?></button>
						<span id="podlove_tracking_export_status_wrapper">
							<?php _e('Export', 'podlove-podcasting-plugin-for-wordpress') ?>: <span id="podlove_tracking_export_status">starting ...</span>
						</span>
					</p>
				</td>
			</tr>
		</table>

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

}