<?php
namespace Podlove\Modules\ImportExport\Settings;

use \Podlove\Settings\Expert\Tabs;

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

		if (defined('SAVEQUERIES') && SAVEQUERIES) {
			add_action('podlove_imexport_settings_head', function() {
				?>
				<div class="error">
					<p>
						<b><?php echo __('Heads up!', 'podlove') ?></b>
						<?php echo __('The WordPress debug option <code>SAVEQUERIES</code> is active. This might lead to memory issues when exporting or importing tracking data.<br>It is probably defined in <code>wp-config.php</code>. Please turn it off before using the export tool.', 'podlove') ?>
					</p>
				</div>
				<?php
			});
		}

		$tabs = new Tabs(__('Import &amp; Export', 'podlove'));
		$tabs->addTab( new Tab\Export(__('Export', 'podlove'), true) );
		$tabs->addTab( new Tab\Import(__('Import', 'podlove')) );
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