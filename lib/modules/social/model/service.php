<?php 
namespace Podlove\Modules\Social\Model;

use \Podlove\Model\Base;
use \Podlove\Modules;

use Podlove\DomDocumentFragment;

class Service extends Base
{
	use \Podlove\Model\KeepsBlogReferenceTrait;

	public function __construct() {
		$this->set_blog_id();
	}

	public function get_logo() {
		return \Podlove\PLUGIN_URL . '/lib/modules/social/images/icons/' . $this->logo;
	}

}

Service::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Service::property( 'category', 'VARCHAR(255)' );
Service::property( 'type', 'VARCHAR(255)' );
Service::property( 'title', 'VARCHAR(255)' );
Service::property( 'description', 'TEXT' );
Service::property( 'logo', 'TEXT' );
Service::property( 'url_scheme', 'TEXT' );