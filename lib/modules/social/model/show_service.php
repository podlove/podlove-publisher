<?php 
namespace Podlove\Modules\Social\Model;

use \Podlove\Model\Base;

/**
 * A contributor contributes to a podcast/show.
 */
class ShowService extends Base {

}

ShowService::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
ShowService::property( 'service_id', 'INT' );
ShowService::property( 'value', 'TEXT' );
ShowService::property( 'title', 'TEXT' );
ShowService::property( 'position', 'INT' );