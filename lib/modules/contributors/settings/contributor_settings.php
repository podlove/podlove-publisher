<?php
namespace Podlove\Modules\Contributors\Settings;

use Podlove\Model;
use \Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Expert\Tabs;

class ContributorSettings {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		ContributorRoles::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Contributor Settings',
			/* $menu_title */ 'Contributor Settings',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_contributor_settings',
			/* $function   */ array( $this, 'page' )
		);

		$tabs = new Tabs( __( 'Contributor Settings', 'podlove' ) );
		$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Groups( __( 'Groups', 'podlove' ), true ) );
		$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Roles( __( 'Roles', 'podlove' ) ) );
		$this->tabs = $tabs;
		$this->tabs->initCurrentTab();

		add_action( 'admin_init', array( '\Podlove\Modules\Contributors\Settings\ContributorGroups', 'process_form' ) );
		add_action( 'admin_init', array( '\Podlove\Modules\Contributors\Settings\ContributorRoles', 'process_form' ) );
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