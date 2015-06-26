<?php
namespace Podlove\Settings;
use \Podlove\Model;

use \Podlove\Settings\Expert\Tabs;
use \Podlove\Settings\Podcast\Tab;

class Podcast {

	use \Podlove\HasPageDocumentationTrait;

	static $pagehook;
	private $tabs;
	
	public function __construct( $handle ) {
		
		Podcast::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Podcast Settings',
			/* $menu_title */ 'Podcast Settings',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_podcast_handle',
			/* $function   */ array( $this, 'page' )
		);

		$this->init_page_documentation(self::$pagehook);

		add_settings_section(
			/* $id 		 */ 'podlove_podcast_general',
			/* $title 	 */ __( 'Podcast Settings', 'podlove' ),	
			/* $callback */ function () { /* section head html */ }, 		
			/* $page	 */ Podcast::$pagehook	
		);

		register_setting( Podcast::$pagehook, 'podlove_podcast', function( $podcast ) {

			if ( $podcast['media_file_base_uri'] )
				$podcast['media_file_base_uri'] = trailingslashit( $podcast['media_file_base_uri'] );
			
			return $podcast;
		} );

		$tabs = new Tabs( __( 'Podcast Settings', 'podlove' ) );
		$tabs->addTab( new Tab\Description( __( 'Description', 'podlove' ), true ) );
		$tabs->addTab( new Tab\Media( __( 'Media', 'podlove' ) ) );
		$tabs->addTab( new Tab\License( __( 'License', 'podlove' ) ) );
		$tabs->addTab( new Tab\Directory( __( 'Directory', 'podlove' ) ) );
		$this->tabs = apply_filters( 'podlove_podcast_settings_tabs', $tabs );
		$this->tabs->initCurrentTab();
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