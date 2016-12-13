<?php 
namespace Podlove\Modules\AnalyticsHeartbeat\Model;

use \Podlove\Model\Base;
use \Podlove\Model\Episode;
use \Podlove\Model\Image;

class Heartbeat extends Base
{	

}

Heartbeat::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
Heartbeat::property('status_start', 'DATETIME');
Heartbeat::property('status_end', 'DATETIME');
Heartbeat::property('status', 'VARCHAR(255)');
Heartbeat::property('beats', 'INT UNSIGNED');
