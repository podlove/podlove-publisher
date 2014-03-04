<?php 
namespace Podlove\Modules\Social\Model;

use \Podlove\Model\Base;

/**
 * A contributor contributes to a podcast/show.
 */
class ShowService extends Base {

	public function get_service() {
		return \Podlove\Modules\Social\Model\Service::find_one_by_id($this->service_id);
	}

	public function get_service_url() {
		$service = $this->get_service();
		return str_replace( '%account-placeholder%', $this->value, $service->url_scheme);
	}

}

ShowService::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
ShowService::property( 'type', 'VARCHAR(255)' );
ShowService::property( 'service_id', 'INT' );
ShowService::property( 'value', 'TEXT' );
ShowService::property( 'title', 'TEXT' );
ShowService::property( 'position', 'FLOAT' );