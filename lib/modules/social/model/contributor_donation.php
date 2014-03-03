<?php 
namespace Podlove\Modules\Social\Model;

use \Podlove\Model\Base;
use \Podlove\Modules\Social\Model\Service;

/**
 * A contributor contributes to a podcast/show.
 */
class ContributorDonation extends Base {

	public function get_service() {
		return \Podlove\Modules\Social\Model\Service::find_one_by_id($this->service_id);
	}

	public function get_service_url() {
		$service = $this->get_service();
		return str_replace( '%account-placeholder%', $this->value, $service->url_scheme);
	}

}

ContributorDonation::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
ContributorDonation::property( 'contributor_id', 'INT' );
ContributorDonation::property( 'service_id', 'INT' );
ContributorDonation::property( 'value', 'TEXT' );
ContributorDonation::property( 'title', 'TEXT' );
ContributorDonation::property( 'position', 'FLOAT' );