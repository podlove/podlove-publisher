<?php
namespace Podlove\Modules\Logging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class WPDBHandler extends AbstractProcessingHandler {

	private $wpdb;

	public function __construct( $wpdb, $level = Logger::DEBUG, $bubble = true ) {
		$this->wpdb = $wpdb;
		parent::__construct( $level, $bubble );
	}

	protected function write( array $record ) {

		$this->wpdb->query( 
			$this->wpdb->prepare( 
				"INSERT INTO " . self::table_name() . " (channel, level, message, time) VALUES (%s, %d, %s, %d)",
			    $record['channel'],
			    $record['level'],
			    $record['formatted'],
			    $record['datetime']->format('U') 
		    )
		);
	}

	public static function table_name() {
		global $wpdb;
		return sprintf( "%spodlove_log", $wpdb->prefix );
	}

	public static function initialize() {
		global $wpdb; 

		$wpdb->query( '
			CREATE TABLE IF NOT EXISTS
			' . self::table_name() . ' 
			(
				channel VARCHAR(255),
				level INTEGER,
				message LONGTEXT,
				time INTEGER UNSIGNED
			)
		' );
	}

}