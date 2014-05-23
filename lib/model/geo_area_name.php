<?php
namespace Podlove\Model;

class GeoAreaName extends Base {

}

GeoAreaName::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
GeoAreaName::property( 'area_id', 'INT' );
GeoAreaName::property( 'language', 'VARCHAR(5)' );
GeoAreaName::property( 'name', 'VARCHAR(255)' );
// GeoAreaName::property( 'media_file_id', 'INT' );
// GeoAreaName::property( 'request_id', 'VARCHAR(32)' );
// GeoAreaName::property( 'accessed_at', 'DATETIME' );
// GeoAreaName::property( 'source', 'VARCHAR(255)' );
// GeoAreaName::property( 'context', 'VARCHAR(255)' );