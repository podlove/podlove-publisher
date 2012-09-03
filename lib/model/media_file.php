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

	public function find_by_episode_id_and_media_location_id( $episode_id, $media_location_id ) {
		
		$where = sprintf(
			'episode_id = "%s" AND media_location_id = "%s"',
			$episode_id,
			$media_location_id
		);

		return MediaFile::find_one_by_where( $where );
	}

	/**
	 * Dynamically return file url from release, format and show.
	 *
	 * @return string
	 */
	public function get_file_url() {

		$podcast  = Podcast::get_instance();

		$episode        = Episode::find_by_id( $this->episode_id );
		$media_location = MediaLocation::find_by_id( $this->media_location_id );
		$media_format   = MediaFormat::find_by_id( $media_location->media_format_id );

		if ( ! $media_location || ! $media_format || ! $episode->slug )
			return '';

		$template = $media_location->url_template;
		$template = str_replace( '%media_file_base_url%', $podcast->media_file_base_uri, $template );
		$template = str_replace( '%episode_slug%',        $episode->slug, $template );
		$template = str_replace( '%format_extension%',    $media_format->extension, $template );

		return $template;
	}

	/**
	 * Build file name as it appears when you download the file.
	 * 
	 * @return string
	 */
	function get_download_file_name() {
		$file_name = $this->episode()->slug
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
MediaFile::property( 'episode_id', 'INT' );
MediaFile::property( 'media_location_id', 'INT' );
MediaFile::property( 'size', 'INT' );