<?php
class Podlove_Show extends Podlove_Table_Base {
	
}

Podlove_Show::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Podlove_Show::property( 'name', 'VARCHAR(255)' );
Podlove_Show::property( 'subtitle', 'VARCHAR(255)' );
Podlove_Show::property( 'slug', 'VARCHAR(255)' );
Podlove_Show::property( 'label', 'VARCHAR(255)' );
Podlove_Show::property( 'episode_prefix', 'VARCHAR(255)' );
Podlove_Show::property( 'media_file_base_uri', 'VARCHAR(255)' );
Podlove_Show::property( 'uri_delimiter', 'VARCHAR(255)' );
Podlove_Show::property( 'episode_number_length', 'INT' );
Podlove_Show::build();