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
		$row = new LogTable();
		$row->channel = $record['channel'];
		$row->level   = $record['level'];
		$row->message = $record['formatted'];
		$row->time    = $record['datetime']->format('U');
		$row->save();
	}

}