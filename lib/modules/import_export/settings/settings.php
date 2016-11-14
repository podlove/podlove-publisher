<?php
namespace Podlove\Modules\ImportExport\Settings;

use \Podlove\Settings\Expert\Tabs;

class Settings {

	static $pagehook;
	
	public function __construct( $handle ) {
		Settings::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ __('Import &amp; Export', 'podlove-podcasting-plugin-for-wordpress'),
			/* $menu_title */ __('Import &amp; Export', 'podlove-podcasting-plugin-for-wordpress'),
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_imexport_migration_handle',
			/* $function   */ array( $this, 'page' )
		);

		if (defined('SAVEQUERIES') && SAVEQUERIES) {
			add_action('podlove_imexport_settings_head', function() {
				?>
				<div class="error">
					<p>
						<b><?php _e('Heads up!', 'podlove-podcasting-plugin-for-wordpress'); ?></b>
						<?php _e('The WordPress debug option <code>SAVEQUERIES</code> is active. This might lead to memory issues when exporting or importing tracking data.<br>It is probably defined in <code>wp-config.php</code>. Please turn it off before using the export tool.', 'podlove-podcasting-plugin-for-wordpress'); ?>
					</p>
				</div>
				<?php
			});
		}

		$tabs = new Tabs(__('Import &amp; Export', 'podlove-podcasting-plugin-for-wordpress'));
		$tabs->addTab( new Tab\Export(__('Export', 'podlove-podcasting-plugin-for-wordpress'), true) );
		$tabs->addTab( new Tab\Import(__('Import', 'podlove-podcasting-plugin-for-wordpress')) );
		$tabs->initCurrentTab();
		$this->tabs = $tabs;
	}

	public function page() {
		?>
		<div class="wrap">
			<?php
			echo $this->tabs->getTabsHTML();
			echo $this->tabs->getCurrentTabPage();
			?>
		</div>	
		<?php
	}
}