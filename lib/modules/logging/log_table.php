<?php 
namespace Podlove\Modules\Logging;
use \Podlove\Log;
use \Podlove\Model;

class LogTable extends Model\Base {

}

LogTable::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
LogTable::property( 'channel', 'VARCHAR(255)' );
LogTable::property( 'level', 'INTEGER' );
LogTable::property( 'message', 'LONGTEXT' );
LogTable::property( 'time', 'INTEGER UNSIGNED' );