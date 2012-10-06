<?php
namespace Podlove\Model;

class EpisodeAsset extends Base {
	
	/**
	 * Sets default values.
	 * 
	 * @return array
	 */
	public function default_values() {
		return array(
			'url_template' => '%media_file_base_url%%episode_slug%.%format_extension%'
		);
	}

	/**
	 * Find the related media format model.
	 *
	 * @return \Podlove\Model\FileType|NULL
	 */
	public function file_type() {
		return FileType::find_by_id( $this->file_type_id );
	}

	/**
	 * Fine all media file models in this location.
	 * 
	 * @return array|NULL
	 */
	function media_files() {
		return MediaFile::find_all_by_episode_asset_id( $this->id );
	}

	public function title() {
		if ( $this->file_type_id )
			return $this->media_format()->title();
		else
			return __( 'Notice: No file format defined.', 'podlove' );
	}

}

EpisodeAsset::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
EpisodeAsset::property( 'title', 'VARCHAR(255)' );
EpisodeAsset::property( 'file_type_id', 'INT' );
EpisodeAsset::property( 'url_template', 'VARCHAR(255)' );
EpisodeAsset::property( 'downloadable', 'INT' );