<?php
namespace Podlove\Model;

class EpisodeAsset extends Base {

	/**
	 * Find the related media format model.
	 *
	 * @return \Podlove\Model\FileType|NULL
	 */
	public function file_type() {
		return FileType::find_by_id( $this->file_type_id );
	}

	/**
	 * Find all media file models in this location.
	 * 
	 * @return array|NULL
	 */
	function media_files() {
		return MediaFile::find_all_by_episode_asset_id( $this->id );
	}

	public function title() {
		if ( $this->file_type_id )
			return $this->file_type()->title();
		else
			return __( 'Notice: No file format defined.', 'podlove' );
	}

}

EpisodeAsset::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
EpisodeAsset::property( 'title', 'VARCHAR(255)' );
EpisodeAsset::property( 'file_type_id', 'INT' );
EpisodeAsset::property( 'suffix', 'VARCHAR(255)' );
EpisodeAsset::property( 'downloadable', 'INT' );