<?php
namespace Podlove\Model;

class Show extends Base {
	
}

Show::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Show::property( 'name', 'VARCHAR(255)' );
Show::property( 'subtitle', 'VARCHAR(255)' );
Show::property( 'slug', 'VARCHAR(255)' );
Show::property( 'label', 'VARCHAR(255)' );
Show::property( 'episode_prefix', 'VARCHAR(255)' );
Show::property( 'media_file_base_uri', 'VARCHAR(255)' );
Show::property( 'uri_delimiter', 'VARCHAR(255)' );
Show::property( 'episode_number_length', 'INT' );
Show::build();