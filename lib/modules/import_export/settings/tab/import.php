<?php
namespace Podlove\Modules\ImportExport\Settings\Tab;

class Import extends \Podlove\Settings\Expert\Tab {

	public function init() {
		$this->page_type = 'custom';

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
							_e('Import successful. Happy podcasting!', 'podlove-podcasting-plugin-for-wordpress');
							break;
						case 'version-warning':
							_e('Heads up: Your export file was exported from a Publisher with a different version. If possible, both Publisher versions should be identical. However, that might not be a problem. Happy podcasting!', 'podlove-podcasting-plugin-for-wordpress');
							break;
					}
					?>
				</p>
			</div>
			<?php
		});
	}

	public function page() {
		do_action('podlove_imexport_settings_head');
		?>

		<p>
			<?php _e('Use this import on <strong>fresh installs only</strong>! Otherwise you may lose data. In any case, you should have backups.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</p>

		<h3><?php _e('Podcast Import', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>

		<form method="POST" enctype="multipart/form-data">
			(<span><?php echo self::get_maximum_upload_size_text() ?></span>)
			<input type="file" name="podlove_import"/> 
			<input type="submit" value="<?php _e('Import Podcast Data', 'podlove-podcasting-plugin-for-wordpress'); ?>" class="button-primary" />
		</form>

		<h3><?php _e('Tracking Import', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>

		<form method="POST" enctype="multipart/form-data">
			(<span><?php echo self::get_maximum_upload_size_text() ?></span>)
			<input type="file" name="podlove_import_tracking"/>
			<input type="submit" value="<?php _e('Import Tracking Data', 'podlove-podcasting-plugin-for-wordpress'); ?>" class="button-primary" />
		</form>

		<?php
	}

	public static function get_maximum_upload_size_text() {
		// this is exactly the same way it is done in wp_import_upload_form()
		$bytes = apply_filters( 'import_upload_size_limit', \wp_max_upload_size() );
		$size = \size_format( $bytes );
		return sprintf( __('Maximum size: %s', 'podlove-podcasting-plugin-for-wordpress'), $size );
	}

}