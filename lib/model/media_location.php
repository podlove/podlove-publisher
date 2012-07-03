<?php
namespace Podlove\Model;

class MediaLocation extends Base {
	
	/**
	 * Sets default values.
	 * 
	 * @return array
	 */
	public function default_values() {
		return array(
			'url_template' => '%media_file_base_url%%episode_slug%%suffix%.%format_extension%'
		);
	}

	/**
	 * Find the related media format model.
	 *
	 * @return \Podlove\Model\MediaFormat|NULL
	 */
	public function media_format() {
		return MediaFormat::find_by_id( $this->media_format_id );
	}

	/**
	 * Fine all media file models in this location.
	 * 
	 * @return array|NULL
	 */
	function media_files() {
		return MediaFile::find_all_by_media_location_id( $this->id );
	}

	/**
	 * Find the related show model.
	 * 
	 * @return \Podlove\Model\Show|NULL
	 */
	public function show() {
		return Show::find_by_id( $this->show_id );
	}

	public function title() {
		if ( $this->media_format_id )
			return $this->media_format()->title();
		else
			return \Podlove\t( 'Notice: No file format defined.' );
	}

}

MediaLocation::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
MediaLocation::property( 'title', 'INT' );
MediaLocation::property( 'show_id', 'INT' );
MediaLocation::property( 'media_format_id', 'INT' );
MediaLocation::property( 'suffix', 'VARCHAR(255)' );
MediaLocation::property( 'url_template', 'VARCHAR(255)' );