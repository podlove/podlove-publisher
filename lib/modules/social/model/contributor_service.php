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

	public static function find_by_contributor_id_and_type( $contributor_id, $type='social' ) {
		return self::all( "WHERE service_id IN (SELECT id FROM " . \Podlove\Modules\Social\Model\Service::table_name() . " WHERE `type` = '" . $type . "' ) AND `contributor_id` = " . $contributor_id );
	}

}

ContributorService::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
ContributorService::property( 'contributor_id', 'INT' );
ContributorService::property( 'service_id', 'INT' );
ContributorService::property( 'value', 'TEXT' );
ContributorService::property( 'title', 'TEXT' );
ContributorService::property( 'position', 'FLOAT' );