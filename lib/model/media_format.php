<?php
namespace Podlove\Model;

class MediaFormat extends Base {
	
	public function title() {
		return $this->name . ' (' . $this->extension . ')';
	}

}

MediaFormat::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
MediaFormat::property( 'name', 'VARCHAR(255)' );
MediaFormat::property( 'type', 'VARCHAR(255)' );
MediaFormat::property( 'mime_type', 'VARCHAR(255)' );
MediaFormat::property( 'extension', 'VARCHAR(255)' );