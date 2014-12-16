<?php
namespace Podlove\Modules\Contributors\Settings;

use Podlove\Model;
use \Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Expert\Tabs;

class ContributorSettings {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		ContributorSettings::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Contributor Settings',
			/* $menu_title */ 'Contributor Settings',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_contributor_settings',
			/* $function   */ array( $this, 'page' )
		);

		$tabs = new Tabs( __( 'Contributor Settings', 'podlove' ) );
		$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Defaults( __( 'Defaults', 'podlove' ), true ) );
		$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Groups( __( 'Groups', 'podlove' ) ) );
		$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Roles( __( 'Roles', 'podlove' ) ) );
		$this->tabs = $tabs;
		$this->tabs->initCurrentTab();

		foreach ($this->tabs->getTabs() as $tab) {
			add_action( 'admin_init', array( $tab->getObject(), 'process_form' ) );
		}
	}
	
	function page() {
		?>
		<div class="wrap">
			<?php
			screen_icon( 'podlove-podcast' );
			echo $this->tabs->getTabsHTML();
			echo $this->tabs->getCurrentTabPage();
			?>
		</div>	
		<?php
	}
	
}