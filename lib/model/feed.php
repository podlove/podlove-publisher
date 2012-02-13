<?php
namespace Podlove\Model;

class Feed extends Base {
	
}

Feed::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Feed::property( 'name', 'VARCHAR(255)' );
Feed::property( 'slug', 'VARCHAR(255)' );
Feed::property( 'uri_pattern', 'VARCHAR(255)' );
Feed::build();