<?php
namespace Podlove\Model;

class GeoArea extends Base {
}

GeoArea::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
GeoArea::property( 'geoname_id', 'INT', array('unique' => true) );
GeoArea::property( 'parent_id', 'INT' );
GeoArea::property( 'code', 'VARCHAR(5)' );
GeoArea::property( 'type', 'VARCHAR(255)' );
