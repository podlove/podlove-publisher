<?php 
namespace Podlove\Modules\Social\Model;

use \Podlove\Model\Base;

/**
 * A contributor contributes to a podcast/show.
 */
class ShowService extends Base {

	use \Podlove\Model\KeepsBlogReferenceTrait;

	public function __construct() {
		$this->set_blog_id();
	}

	public function get_service() {
		return \Podlove\Modules\Social\Model\Service::find_one_by_id($this->service_id);
	}

	public function get_service_url() {
		$service = $this->get_service();
		return str_replace( '%account-placeholder%', $this->value, $service->url_scheme);
	}

	public static function find_by_category( $category = 'social' ) {
		return self::all( "WHERE service_id IN (SELECT id FROM " . \Podlove\Modules\Social\Model\Service::table_name() . " WHERE `category` = '" . $category . "' ) ORDER BY position ASC" );
	}

}

ShowService::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
ShowService::property( 'service_id', 'INT' );
ShowService::property( 'value', 'TEXT' );
ShowService::property( 'title', 'TEXT' );
ShowService::property( 'position', 'FLOAT' );