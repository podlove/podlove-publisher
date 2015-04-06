<?php
namespace Podlove\Model;

class EpisodeAsset extends Base {

	use KeepsBlogReferenceTrait;

	public function __construct() { $this->set_blog_id(); }

	public function save() {
		global $wpdb;

		if ( ! $this->position ) {
			$pos = $wpdb->get_var( sprintf( 'SELECT MAX(position)+1 FROM %s', self::table_name() ) );
			$this->position = $pos ? $pos : 1;
		}

		parent::save();
		
		$this->maybe_connect_to_web_player();
	}

	/**
	 * Find the related media format model.
	 *
	 * @return \Podlove\Model\FileType|NULL
	 */
	public function file_type() {
		return $this->with_blog_scope(function() {
			return FileType::find_by_id( $this->file_type_id );
		});
	}

	/**
	 * Find all media file models in this location.
	 * 
	 * @return array|NULL
	 */
	function media_files() {
		return $this->with_blog_scope(function() {
			return MediaFile::find_all_by_episode_asset_id( $this->id );
		});
	}

	/**
	 * Find all media files with a size > 0.
	 *
	 * @todo performance (1+n)
	 * @return array|NULL
	 */
	public function active_media_files() {
		return array_filter( $this->media_files(), function( $file ) {
			if ( $file->size <= 0 )
				return false;

			return in_array( get_post( $file->episode()->post_id )->post_status, array( 'publish', 'private', 'draft', 'future' ) );
		} );
	}

	public function title() {
		if ( $this->file_type_id )
			return $this->file_type()->title();
		else
			return __( 'Notice: No file format defined.', 'podlove' );
	}

	/**
	 * Checks if asset is used by web player.
	 * 
	 * @return boolean true if connected to any web player asset, otherwise false.
	 */
	public function is_connected_to_web_player() {

		foreach ( get_option( 'podlove_webplayer_formats', array() ) as $_ => $media_types )
			foreach ( $media_types as $asset_id )
				if ( $asset_id == $this->id )
					return true;

		return false;
	}

	/**
	 * Use for web player if this web player slot is not yet taken.
	 */
	public function maybe_connect_to_web_player() {
		$webplayer_formats = get_option( 'podlove_webplayer_formats', array() );
		$allowed_formats = \Podlove\Settings\Expert\Tab\WebPlayer::formats();
		$asset_type = $this->file_type()->mime_type;
		$type = substr( $asset_type, 0, stripos( $asset_type, '/' ) );

		if ( isset( $allowed_formats[ $type ] ) ) {
			foreach ( $allowed_formats[ $type ] as $extension => $format_data ) {
				if ( in_array($asset_type, $format_data['mime_types']) ) {
					
					if ( ! isset( $webplayer_formats[ $type ] ) )
						$webplayer_formats[ $type ] = array();

					if ( ! isset( $webplayer_formats[ $type ][ $extension ] ) || ! $webplayer_formats[ $type ][ $extension ] ) {
						$webplayer_formats[ $type ][ $extension ] = $this->id;
						update_option( 'podlove_webplayer_formats', $webplayer_formats );
					}

					break;
				}
			}
		}
	}

	/**
	 * Checks if asset is connected to any feed.
	 * 
	 * @return boolean true if connected to any feed, otherwise false.
	 */
	public function is_connected_to_feed() {
		return (bool) Feed::find_one_by_episode_asset_id( $this->id );
	}

	/**
	 * Checks if asset has an active media file.
	 *
	 * A media file is active if its file size is > 0.
	 * 
	 * @return boolean true if any media file has a size > 0, otherwise false.
	 */
	public function has_active_media_files() {
		return count( $this->active_media_files() ) > 0;
	}

	/**
	 * Checks if asset is assigned as image or chapter asset.
	 * 
	 * @return boolean true if assigned, otherwise false.
	 */
	public function has_asset_assignments() {
		$assignment = AssetAssignment::get_instance();
		return in_array( $this->id, array( $assignment->image, $assignment->chapters ) );
	}

	/**
	 * Checks if asset should be deleted.
	 *
	 * Can only be deleted if all of the following applies to the asset:
	 * - has no active media file
	 * - has no asset assignment
	 * - is not connected to any feed
	 * - is not connected to web player
	 * 
	 * @return boolean true if it should be deleted, otherwise false.
	 */
	public function is_deletable() {
		return ! $this->has_active_media_files()
			&& ! $this->has_asset_assignments()
			&& ! $this->is_connected_to_feed()
			&& ! $this->is_connected_to_web_player();
	}

	/**
	 * @override \Podlove\Model\Base::delete();
	 */
	public function delete() {
		foreach ( $this->media_files() as $media_file ) {
			$media_file->delete();
		}
		parent::delete();
	}

}

EpisodeAsset::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
EpisodeAsset::property( 'title', 'VARCHAR(255)' );
EpisodeAsset::property( 'file_type_id', 'INT' );
EpisodeAsset::property( 'suffix', 'VARCHAR(255)' );
EpisodeAsset::property( 'downloadable', 'INT' );
EpisodeAsset::property( 'position', 'FLOAT' );
