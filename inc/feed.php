<?php
class Podlove_Feed extends Podlove_Table_Base {
	
}

Podlove_Feed::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Podlove_Feed::property( 'name', 'VARCHAR(255)' );
Podlove_Feed::property( 'slug', 'VARCHAR(255)' );
Podlove_Feed::property( 'uri_pattern', 'VARCHAR(255)' );
Podlove_Feed::build();