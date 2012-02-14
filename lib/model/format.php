<?php
namespace Podlove\Model;

class Format extends Base {
	
}

Format::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Format::property( 'name', 'VARCHAR(255)' );
Format::property( 'slug', 'VARCHAR(255)' );
Format::property( 'type', 'VARCHAR(255)' );
Format::property( 'mime_type', 'VARCHAR(255)' );
Format::property( 'extension', 'VARCHAR(255)' );