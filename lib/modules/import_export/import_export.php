<?php
namespace Podlove\Modules\ImportExport;

class Import_Export extends \Podlove\Modules\Base {

	protected $module_name = 'Import &amp; Export';
	protected $module_description = 'Import &amp; export podlove data for backup or migration to another WordPress instance.';
	protected $module_group = 'system';

	public function load() {
		
		Export\Podcast::init();
		Export\Tracking::init();

		add_action( 'admin_menu', array( $this, 'register_menu' ), 250 );
	}

	public function register_menu() {
		new Settings\Settings( \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE );
	}

}