<?php 
namespace Podlove\Modules\Social\Model;

use \Podlove\Model\Base;
use \Podlove\Modules;

use Podlove\DomDocumentFragment;

class Service extends Base
{

	public function get_logo() {
		return \Podlove\PLUGIN_URL . '/lib/modules/social/images/icons/' . $this->logo;
	}

}

Service::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Service::property( 'type', 'VARCHAR(255)' );
Service::property( 'title', 'VARCHAR(255)' );
Service::property( 'name', 'VARCHAR(255)' );
Service::property( 'description', 'TEXT' );
Service::property( 'logo', 'TEXT' );
Service::property( 'url_scheme', 'TEXT' );