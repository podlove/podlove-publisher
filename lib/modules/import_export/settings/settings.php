<?php
namespace Podlove\Modules\ImportExport\Settings;

class Settings {

	static $pagehook;
	
	public function __construct( $handle ) {
		Settings::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Import &amp; Export',
			/* $menu_title */ 'Import &amp; Export',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_imexport_migration_handle',
			/* $function   */ array( $this, 'page' )
		);

		add_action('admin_notices', function() {

			if (!isset($_GET['page']))
				return false;

			if ($_GET['page'] != 'podlove_imexport_migration_handle')
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

	public static function get_maximum_upload_size_text() {
		// this is exactly the same way it is done in wp_import_upload_form()
		$bytes = apply_filters( 'import_upload_size_limit', \wp_max_upload_size() );
		$size = \size_format( $bytes );
		return sprintf( __('Maximum size: %s' ), $size );
	}

	public function page() {
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __('Import &amp; Export', 'podlove') ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row" valign="top" colspan="2">
						<h3 style="margin-bottom: 0px"><?php echo __('Export', 'podlove') ?></h3>
					</th>
				</tr>
				<tr>
					<td colspan="2">
						<?php echo sprintf(
							__('This export complements the existing %sWordPress export tool%s. It contains all relevant podcast data to enable you to move from this WordPress instance to another. Step by step:', 'podlove'),
							'<a href="' . admin_url('export.php') . '">', '</a>'
						);
						?>
						<ol>
							<li>
								<?php echo sprintf(
									__('Go to the %sWordPress export tool%s and export all data.', 'podlove'),
									'<a href="' . admin_url('export.php') . '">', '</a>' 
								); ?>
							</li>
							<li><?php echo __('Import this file to your new WordPress instance.', 'podlove') ?></li>
							<li><?php echo __('Use the button below to export the podcast data file.', 'podlove') ?></li>
							<li><?php echo __('In your new WordPress instance, import that file.', 'podlove') ?></li>
						</ol>

						<a href="?podlove_export=1" class="button-primary"><?php echo __('Export Podcast Data', 'podlove') ?></a>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top" colspan="2">
						<h3 style="margin-bottom: 0px"><?php echo __('Import', 'podlove') ?></h3>
					</th>
				</tr>
				<tr>
					<td colspan="2">
						<?php echo __('Heads up: Use this import on <strong>fresh installs only</strong>! Otherwise you may lose data. In any case, you should have backups.', 'podlove'); ?>
					</td>
				</tr>
				<tr>
					<td><?php echo __('Import File', 'podlove') ?></td>
					<td>
						<form method="POST" enctype="multipart/form-data">
							(<span><?php echo self::get_maximum_upload_size_text() ?></span>)
							<input type="file" name="podlove_import"/> 
							<input type="submit" value="<?php echo __('Import Podcast Data', 'podlove') ?>" class="button-primary" />
						</form>
					</td>
				</tr>
			</table>

			<hr>
			<h2><?php echo __('Tracking Import &amp; Export', 'podlove') ?></h2>

			<?php if ( defined('SAVEQUERIES') && SAVEQUERIES ): ?>
				<div class="error">
					<p>
						<b><?php echo __('Heads up!', 'podlove') ?></b>
						<?php echo __('The WordPress debug option <code>SAVEQUERIES</code> is active. This might lead to memory issues when exporting or importing tracking data. It is probably defined in <code>wp-config.php</code>.', 'podlove') ?>
					</p>
				</div>
			<?php endif; ?>

			<table class="form-table">
				<tr>
					<th scope="row" valign="top" colspan="2">
						<h3 style="margin-bottom: 0px"><?php echo __('Export', 'podlove') ?></h3>
					</th>
				</tr>
				<tr>
					<td colspan="2">
						<p>
							<?php echo __('Im- and export of tracking data is a separate task. After you have completed the steps above, you can ex- and import tracking data.', 'podlove'); ?>
						</p>
						<p>
							<button id="podlove_tracking_export" class="button-primary"><?php echo __('Export Tracking Data', 'podlove') ?></button>
							<span id="podlove_tracking_export_status_wrapper">
								<?php echo __('Export', 'podlove') ?>: <span id="podlove_tracking_export_status">starting ...</span>
							</span>
						</p>
					</td>
				</tr>
				<tr>
					<td><?php echo __('Import File', 'podlove') ?></td>
					<td>
						<form method="POST" enctype="multipart/form-data">
							(<span><?php echo self::get_maximum_upload_size_text() ?></span>)
							<input type="file" name="podlove_import_tracking"/>
							<input type="submit" value="<?php echo __('Import Tracking Data', 'podlove') ?>" class="button-primary" />
						</form>
					</td>
				</tr>
			</table>

		</div>

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