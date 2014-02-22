<?php 
namespace Podlove\Modules\Social\Model;

use \Podlove\Model\Base;

class Service extends Base
{

	public function get_log() {
		return $this->get_module_url() . '/images/icons/' . $this->logo;
	}

}

Service::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Service::property( 'title', 'VARCHAR(255)' );
Service::property( 'description', 'TEXT' );
Service::property( 'logo', 'TEXT' );
Service::property( 'url_scheme', 'TEXT' );