<?php 
namespace Podlove\Modules\Logging;
use \Podlove\Log;

class Logging extends \Podlove\Modules\Base {

	protected $module_name = 'Logging';
	protected $module_description = 'View podlove related logs in dashboard. (writes logs to database)';

	public function load() {
		add_action( 'podlove_module_was_activated_logging', array( $this, 'was_activated' ) );
		add_action( 'init', array( $this, 'register_database_logger' ));
	}

	public function was_activated( $module_name ) {
		LogTable::build();
	}

	public function register_database_logger() {
		global $wpdb;

		$log = Log::get();
		$log->pushHandler( new WPDBHandler( $wpdb, $log->get_log_level() ) );
	}

}