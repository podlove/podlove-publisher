<?php
namespace Podlove\Model;

class File extends Base {

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

	public function find_or_create_by_release_id_and_format_id( $release_id, $format_id ) {

		$file = File::find_by_release_id_and_format_id( $release_id, $format_id );
		
		if ( $file )
			return $file;

		$file = new File();
		$file->release_id = $release_id;
		$file->format_id = $format_id;
		$file->save();

		return $file;
	}

	public function find_by_release_id_and_format_id( $release_id, $format_id ) {
		$where = sprintf( 'release_id = "%s" AND format_id = "%s"', $release_id, $format_id );
		return File::find_one_by_where( $where );
	}

	/**
	 * Dynamically return file url from release, format and show.
	 *
	 * @return string
	 */
	public function get_file_url() {
		$release = Release::find_by_id( $this->release_id );
		$format  = Format::find_by_id( $this->format_id );
		$show    = Show::find_by_id( $release->show_id );

		$url = $show->media_file_base_uri
		     . $release->slug
		     . $format->suffix
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

File::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
File::property( 'release_id', 'INT' );
File::property( 'format_id', 'INT' );
File::property( 'size', 'INT' );