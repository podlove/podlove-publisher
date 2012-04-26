<?php
namespace Podlove\Model;

class MediaFile extends Base {

	/**
	 * Fetches file size if necessary.
	 *
	 * @override Base::save()
	 */
	public function save() {

		if ( ! $this->size ) {
			$this->determine_file_size();
		}

		return parent::save();
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
		$format   = MediaFormat::find_by_id( $location->format_id );
		$show     = Show::find_by_id( $release->show_id );
		$feed     = Feed::find_by_show_id_and_format_id( $show->id, $format->id );

		$url = $show->media_file_base_uri
		     . $release->slug
		     . $location->suffix
		     . '.'
		     . $format->extension;

		return $url;
	}

	/**
	 * Determine file size by reading the HTTP Header of the file url.
	 *
	 * @return void
	 */
	private function determine_file_size() {
		$header     = $this->curl_get_header();
		$this->size = $header[ 'download_content_length' ];
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