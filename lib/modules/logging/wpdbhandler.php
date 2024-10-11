<?php

namespace Podlove\Modules\Logging;

use PodlovePublisher_Vendor\Monolog\Handler\AbstractProcessingHandler;
use PodlovePublisher_Vendor\Monolog\Logger;

class WPDBHandler extends AbstractProcessingHandler
{
    private $wpdb;

    public function __construct($wpdb, $level = Logger::DEBUG, $bubble = true)
    {
        $this->wpdb = $wpdb;
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $row = new LogTable();
        $row->channel = $record['channel'];
        $row->level = $record['level'];
        $row->message = esc_sql($record['message']);
        $row->context = wp_json_encode($record['context']);
        $row->time = $record['datetime']->format('U');
        $row->save();
    }
}
