<?php
namespace Podlove\Model;

class MediaLocation extends Base {
	
	/**
	 * Find the related media format model.
	 *
	 * @return \Podlove\Model\MediaFormat|NULL
	 */
	public function media_format() {
		return MediaFormat::find_by_id( $this->media_format_id );
	}

	/**
	 * Find the related show model.
	 * 
	 * @return \Podlove\Model\Show|NULL
	 */
	public function show() {
		return Show::find_by_id( $this->show_id );
	}

}

MediaLocation::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
MediaLocation::property( 'show_id', 'INT' );
MediaLocation::property( 'media_format_id', 'INT' );
MediaLocation::property( 'suffix', 'VARCHAR(255)' );
MediaLocation::property( 'url_template', 'VARCHAR(255)' );