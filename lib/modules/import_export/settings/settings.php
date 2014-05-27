<?php
namespace Podlove\Modules\ImportExport\Settings;

use Podlove\Modules\ImportExport\Importer;

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

		if (isset($_FILES['podlove_import'])) {

			// allow xml uploads
			add_filter('upload_mimes', function ($mimes) {
			    return array_merge($mimes, array('xml' => 'application/xml'));
			});
			 
			$file = wp_handle_upload($_FILES['podlove_import'], array('test_form' => false));
			if ($file) {
				update_option('podlove_import_file', $file['file']);
				$this->import();
			} else {
				// file upload didn't work
			}
		}
	}

	public function import()
	{
		if (!($file = get_option('podlove_import_file')))
			return;

		$importer = new Importer($file);
		$importer->import();
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
							<input type="file" name="podlove_import"/>
							<input type="submit" value="<?php echo __('Import Podcast Data', 'podlove') ?>" class="button-primary" />
						</form>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
}