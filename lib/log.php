<?php
namespace Podlove;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Podlove Logger class.
 *
 * @see  https://github.com/Seldaek/monolog for documentation
 *
 * Example usage:
 *   use Podlove\Log;
 *   
 *   Log::get()->addWarning( 'This is a warning.' );
 *   Log::get()->addWarning( 'This is another warning.', array( 'comment' => 'additional info' ) );
 */
class Log {

	private static $instance;
	private $log;

	private function __construct() {
		$log = new Logger( 'Podlove' );
		$log->pushHandler( new StreamHandler( 'php://stderr', WP_DEBUG ? Logger::DEBUG : Logger::INFO ) );
		$this->log = $log;
	}

	public static function get() {

	    if ( ! isset( self::$instance ) )
	        self::$instance = new self;

	    return self::$instance;
	}

	/**
	 * Proxy calls to Logger instance.
	 * 
	 * @param  strong $name      method name
	 * @param  array $arguments
	 */
	public function __call( $name, $arguments ) {

		if ( method_exists( $this->log, $name ) )
			call_user_method_array( $name, $this->log, $arguments );
	}

	public function __clone() {
	    trigger_error( 'Singleton. Cloning not allowed.', E_USER_ERROR );
	}

	public function __wakeup() {
	    trigger_error( 'Singleton. Deserialisation not allowed.', E_USER_ERROR );
	}
}