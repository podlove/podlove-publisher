<?php 
namespace Podlove\Modules\Social\Model;

use \Podlove\Model\Base;
use \Podlove\Model\Image;
use \Podlove\Modules;

use Podlove\DomDocumentFragment;

class Service extends Base
{
	use \Podlove\Model\KeepsBlogReferenceTrait;

	public function __construct() {
		$this->set_blog_id();
	}

	/**
	 * @deprecated since 2.2.0, use ::image() instead
	 */
	public function get_logo() {
		return \Podlove\PLUGIN_URL . '/lib/modules/social/images/icons/' . $this->logo;
	}

	public function image() {
		return new Image(\Podlove\PLUGIN_URL . '/lib/modules/social/images/icons/' . $this->logo, $this->title);
	}

}

Service::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Service::property( 'category', 'VARCHAR(255)' );
Service::property( 'type', 'VARCHAR(255)' );
Service::property( 'title', 'VARCHAR(255)' );
Service::property( 'description', 'TEXT' );
Service::property( 'logo', 'TEXT' );
Service::property( 'url_scheme', 'TEXT' );