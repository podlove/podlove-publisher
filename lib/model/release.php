<?php
namespace Podlove\Model;

class Release extends Base {

	/**
	 * Sets default values.
	 * 
	 * @return array
	 */
	public function default_values() {
		return array(
			'active' => true,
			'enable' => true
		);
	}

	/**
	 * Find the related show model.
	 *
	 * @return \Podlove\Model\Show|NULL
	 */
	public function show() {
		return Show::find_by_id( $this->show_id );
	}

	/**
	 * Find the related episode model.
	 * 
	 * @return \Podlove\Model\Episode|NULL
	 */
	public function episode() {
		return Episode::find_by_id( $this->episode_id );
	}

	/**
	 * Find all related media file models.
	 * 
	 * @return array
	 */
	public function media_files() {
		return MediaFile::find_all_by_release_id( $this->id );
	}


	public function find_or_create_by_episode_id_and_show_id( $episode_id, $show_id ) {
		$where = sprintf( 'episode_id = "%s" AND show_id = "%s"', $episode_id, $show_id );
		$release = Release::find_one_by_where( $where );

		if ( $release )
			return $release;

		$release = new Release();
		$release->episode_id = $episode_id;
		$release->show_id = $show_id;
		$release->save();

		return $release;
	}

	function enclosure_url( $show, $media_location, $format ) {
		$template = $media_location->url_template;

		$template = str_replace( '%show_base_uri%', $show->media_file_base_uri, $template );
		$template = str_replace( '%episode_slug%', $this->slug, $template );
		$template = str_replace( '%suffix%', $media_location->suffix, $template );
		$template = str_replace( '%format_extension%', $format->extension, $template );

		return $template;
	}
	
}

Release::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Release::property( 'show_id', 'INT' );
Release::property( 'episode_id', 'INT' );
Release::property( 'active', 'INT' ); // publicized or not?
Release::property( 'enable', 'INT' ); // listed in podcast directories or not?
Release::property( 'slug', 'VARCHAR(255)' );
Release::property( 'duration', 'VARCHAR(255)' );
Release::property( 'cover_art', 'VARCHAR(255)' );