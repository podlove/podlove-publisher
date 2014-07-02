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