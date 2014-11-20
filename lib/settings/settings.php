<?php
namespace Podlove\Settings;
use \Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Expert\Tabs;

/**
 * Expert Settings
 */
class Settings {

	static $pagehook;
	private $tabs;
	
	public function __construct( $handle ) {
		
		Settings::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Expert Settings',
			/* $menu_title */ 'Expert Settings',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_settings_handle',
			/* $function   */ array( $this, 'page' )
		);

		$tabs = new Tabs( __( 'Expert Settings', 'podlove' ) );
		$tabs->addTab( new Tab\Website( __( 'Website', 'podlove' ), true ) );
		$tabs->addTab( new Tab\Metadata( __( 'Metadata', 'podlove' ) ) );
		$tabs->addTab( new Tab\Redirects( __( 'Redirects', 'podlove' ) ) );
		$tabs->addTab( new Tab\WebPlayer( __( 'Web Player', 'podlove' ) ) );
		$tabs->addTab( new Tab\FileTypes( __( 'File Types', 'podlove' ) ) );
		$tabs->addTab( new Tab\Tracking( __( 'Tracking', 'podlove' ) ) );
		$this->tabs = $tabs;
		$this->tabs->initCurrentTab();
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