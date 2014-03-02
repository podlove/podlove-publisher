<?php 
namespace Podlove\Modules\Social\Model;

use \Podlove\Model\Base;
use \Podlove\Modules\Social\Model\Service;

/**
 * A contributor contributes to a podcast/show.
 */
class ContributorService extends Base {

	public function get_service() {
		return \Podlove\Modules\Social\Model\Service::find_one_by_id($this->service_id);
	}

	public function get_service_url() {
		$service = $this->get_service();
		return str_replace( '%account-placeholder%', $this->value, $service->url_scheme);
	}

}

ContributorService::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
ContributorService::property( 'contributor_id', 'INT' );
ContributorService::property( 'service_id', 'INT' );
ContributorService::property( 'value', 'TEXT' );
ContributorService::property( 'title', 'TEXT' );
ContributorService::property( 'position', 'FLOAT' );