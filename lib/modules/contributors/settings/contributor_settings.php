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
			/* $page_title */ 'Contributors',
			/* $menu_title */ 'Contributors',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_contributor_settings',
			/* $function   */ array( $this, 'page' )
		);

		$tabs = new Tabs( __( 'Contributors', 'podlove-podcasting-plugin-for-wordpress' ) );
		$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Contributors( __( 'Contributors', 'podlove-podcasting-plugin-for-wordpress' ), true ) );
		$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Groups( __( 'Groups', 'podlove-podcasting-plugin-for-wordpress' ) ) );
		$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Roles( __( 'Roles', 'podlove-podcasting-plugin-for-wordpress' ) ) );
		$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Defaults( __( 'Defaults', 'podlove-podcasting-plugin-for-wordpress' ) ) );
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