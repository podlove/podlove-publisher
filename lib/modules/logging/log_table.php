<?php 
namespace Podlove\Modules\Logging;
use \Podlove\Log;
use \Podlove\Model;

class LogTable extends Model\Base {

	/**
	 * Only keep logs for 4 weeks.
	 */
	public static function cleanup() {
		global $wpdb;

		$wpdb->query('DELETE FROM ' . LogTable::table_name() . ' WHERE time < ' . strtotime("-4 weeks"));
	}
}

LogTable::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
LogTable::property( 'channel', 'VARCHAR(255)' );
LogTable::property( 'level', 'INTEGER' );
LogTable::property( 'message', 'LONGTEXT' );
LogTable::property( 'context', 'LONGTEXT' );
LogTable::property( 'time', 'INTEGER UNSIGNED' );