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
			/* $page_title */ __( 'Contributors', 'podlove-podcasting-plugin-for-wordpress' ),
			/* $menu_title */ __( 'Contributors', 'podlove-podcasting-plugin-for-wordpress' ),
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_contributor_settings',
			/* $function   */ array( $this, 'page' )
		);

		if (filter_input(INPUT_GET, 'page') == 'podlove_contributor_settings') {
			$tabs = new Tabs( __( 'Contributors', 'podlove-podcasting-plugin-for-wordpress' ) );
			$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Contributors( __( 'Contributors', 'podlove-podcasting-plugin-for-wordpress' ), true ) );
			$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Groups( __( 'Groups', 'podlove-podcasting-plugin-for-wordpress' ) ) );
			$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Roles( __( 'Roles', 'podlove-podcasting-plugin-for-wordpress' ) ) );
			$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Defaults( __( 'Defaults', 'podlove-podcasting-plugin-for-wordpress' ) ) );

			$tabs = apply_filters('podlove_contributor_settings_tabs', $tabs);

			$this->tabs = $tabs;
			$this->tabs->initCurrentTab();

			foreach ($this->tabs->getTabs() as $tab) {
				if (method_exists($tab, 'getObject')) {
					add_action( 'admin_init', array( $tab->getObject(), 'process_form' ) );
				}
			}
		}
	}
	
	function page() {
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
