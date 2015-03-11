<?php
namespace Podlove\Model;

class GeoAreaName extends Base {

	use NetworkTrait;
	
}

GeoAreaName::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
GeoAreaName::property( 'area_id', 'INT' );
GeoAreaName::property( 'language', 'VARCHAR(5)' );
GeoAreaName::property( 'name', 'VARCHAR(255)' );
