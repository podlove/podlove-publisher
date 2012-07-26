<?php
namespace Podlove\Model;

class MediaFile extends Base {

	/**
	 * Fetches file size if necessary.
	 *
	 * @override Base::save()
	 */
	public function save() {

		if ( ! $this->size || $this->size < 1 ) {
			$this->determine_file_size();
		}

		return parent::save();
	}

	/**
	 * Find the related show model.
	 *
	 * @return \Podlove\Model\MediaLocation|NULL
	 */
	public function media_location() {
		return MediaLocation::find_by_id( $this->media_location_id );
	}

	/**
	 * Find the related release model.
	 *
	 * @return \Podlove\Model\Release|NULL
	 */
	public function release() {
		return Release::find_by_id( $this->release_id );
	}

	public function find_or_create_by_release_id_and_media_location_id( $release_id, $media_location_id ) {

		$file = File::find_by_release_id_and_media_location_id( $release_id, $media_location_id );
		
		if ( $file )
			return $file;

		$file = new MediaFile();
		$file->release_id = $release_id;
		$file->media_location_id = $media_location_id;
		$file->save();

		return $file;
	}

	public function find_by_release_id_and_media_location_id( $release_id, $media_location_id ) {
		$where = sprintf( 'release_id = "%s" AND media_location_id = "%s"', $release_id, $media_location_id );
		return MediaFile::find_one_by_where( $where );
	}

	/**
	 * Dynamically return file url from release, format and show.
	 *
	 * @return string
	 */
	public function get_file_url() {
		$release  = Release::find_by_id( $this->release_id );
		$location = MediaLocation::find_by_id( $this->media_location_id );
		$format   = MediaFormat::find_by_id( $location->media_format_id );
		$show     = Show::find_by_id( $release->show_id );

		return $release->enclosure_url( $show, $this->media_location(), $format );
	}

	/**
	 * Build file name as it appears when you download the file.
	 * 
	 * @return string
	 */
	function get_download_file_name() {
		$file_name = $this->release()->slug
		           . '.'
		           . $this->media_location()->media_format()->extension;
		           
		return apply_filters( 'podlove_download_file_name', $file_name, $this );
	}

	/**
	 * Determine file size by reading the HTTP Header of the file url.
	 *
	 * @return void
	 */
	private function determine_file_size() {
		$header     = $this->curl_get_header();
		$this->size = $header['download_content_length'];
	}

	/**
	 * Retrieve header data via curl.
	 *
	 * @return array
	 */
	function curl_get_header() {
		$curl = curl_init();

		curl_setopt( $curl, CURLOPT_URL, $this->get_file_url() );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ); // make curl_exec() return the result
		curl_setopt( $curl, CURLOPT_HEADER, true );         // header only
		curl_setopt( $curl, CURLOPT_NOBODY, true );         // return no body; HTTP request method: HEAD
		curl_setopt( $curl, CURLOPT_FAILONERROR, true );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true ); // follow redirects
		curl_setopt( $curl, CURLOPT_MAXREDIRS, 5 );         // maximum number of redirects
		
		curl_exec( $curl );
		$info = curl_getinfo( $curl );
		curl_close( $curl );
		
		return $info;
	}
	
}

MediaFile::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
MediaFile::property( 'release_id', 'INT' );
MediaFile::property( 'media_location_id', 'INT' );
MediaFile::property( 'size', 'INT' );