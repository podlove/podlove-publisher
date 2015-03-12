<?php
namespace Podlove\Model;

class FileType extends Base {
	
	public function title() {
		return $this->name . ' (' . $this->extension . ')';
	}

	public static function get_types() {
		global $wpdb;
		return $wpdb->get_col( "SELECT DISTINCT `type` FROM " . FileType::table_name() );
	}

}

FileType::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
FileType::property( 'name', 'VARCHAR(255)' );
FileType::property( 'type', 'VARCHAR(255)' );
FileType::property( 'mime_type', 'VARCHAR(255)' );
FileType::property( 'extension', 'VARCHAR(255)' );