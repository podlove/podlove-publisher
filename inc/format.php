<?php
class Podlove_Format extends Podlove_Table_Base {
	
}

Podlove_Format::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Podlove_Format::property( 'name', 'VARCHAR(255)' );
Podlove_Format::property( 'slug', 'VARCHAR(255)' );
Podlove_Format::property( 'type', 'VARCHAR(255)' );
Podlove_Format::property( 'mime_type', 'VARCHAR(255)' );
Podlove_Format::property( 'extension', 'VARCHAR(255)' );
Podlove_Format::build();