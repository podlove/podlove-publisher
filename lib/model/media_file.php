<?php
namespace Podlove\Model;
use Podlove\Log;

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

	/**
	 * Find the related show model.
	 *
	 * @return \Podlove\Model\EpisodeAsset|NULL
	 */
	public function episode_asset() {
		return EpisodeAsset::find_by_id( $this->episode_asset_id );
	}

	public function find_or_create_by_episode_id_and_episode_asset_id( $episode_id, $episode_asset_id ) {
		
		if ( ! $file = self::find_by_episode_id_and_episode_asset_id( $episode_id, $episode_asset_id ) ) {
			$file = new MediaFile();
			$file->episode_id = $episode_id;
			$file->episode_asset_id = $episode_asset_id;
			$file->save();
		}

		return $file;
	}

	public function find_by_episode_id_and_episode_asset_id( $episode_id, $episode_asset_id ) {
		
		$where = sprintf(
			'episode_id = "%s" AND episode_asset_id = "%s"',
			$episode_id,
			$episode_asset_id
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

		$episode       = $this->episode();
		$episode_asset = EpisodeAsset::find_by_id( $this->episode_asset_id );
		$file_type     = FileType::find_by_id( $episode_asset->file_type_id );

		if ( ! $episode_asset || ! $file_type )
			return '';

		$template = $podcast->get_url_template();
		$template = apply_filters( 'podlove_file_url_template', $template );
		$template = str_replace( '%media_file_base_url%', trailingslashit( $podcast->media_file_base_uri ), $template );
		$template = str_replace( '%episode_slug%',        \Podlove\slugify( $episode->slug ), $template );
		$template = str_replace( '%suffix%',              $episode_asset->suffix, $template );
		$template = str_replace( '%format_extension%',    $file_type->extension, $template );

		return $template;
	}

	/**
	 * Dynamically return file path from release, format and show.
	 *
	 * @return string
	 */
	public function get_file_path() {
		
		$url_data  = parse_url( $this->get_file_url() );
		
		return trim( $url_data['path'], '/' );
	}

	public function episode() {
		return Episode::find_by_id( $this->episode_id );
	}

	/**
	 * Build file name as it appears when you download the file.
	 * 
	 * @return string
	 */
	function get_download_file_name() {

		$file_name = $this->episode()->slug
		           . '.'
		           . $this->episode_asset()->file_type()->extension;
		           
		return apply_filters( 'podlove_download_file_name', $file_name, $this );
	}

	/**
	 * Determine file size by reading the HTTP Header of the file url.
	 *
	 * @return void
	 */
	public function determine_file_size() {
		$header     = $this->curl_get_header();

		if ( (int) $header["http_code"] !== 304 )
			$this->size = $header['download_content_length'];

		return $header;
	}

	/**
	 * Retrieve header data via curl.
	 *
	 * @return array
	 */
	function curl_get_header() {
		$response = self::curl_get_header_for_url( $this->get_file_url(), $this->etag );
		$this->validate_request_header( $response );
		return $response['header'];
	}

	/**
	 * Validate media file headers.
	 *
	 * @todo  $this->id not available for first validation before media_file has been saved
	 * @param  array $response curl response
	 */
	private function validate_request_header( $response ) {

		$header = $response['header'];

		// look for ETag and safe for later
		if ( preg_match( '/ETag:\s*"([^"]+)"/i', $response['response'], $matches ) ) {
			$this->etag = $matches[1];
		}

		// skip validation if ETag did not change
		if ( (int) $header["http_code"] === 304 ) {
			Log::get()->addInfo(
				'Validating media file: File Not Modified.',
				array( 'media_file_id' => $this->id )
			);
			return;
		}

		// verify HTTP header
		if ( ! preg_match( "/^[23]\d\d$/", $header["http_code"] ) ) {
			Log::get()->addError(
				'Unexpected http response when trying to access remote media file.',
				array( 'media_file_id' => $this->id, 'http_code' => $header["http_code"] )
			);
			return;
		}

		// check if content length has changed
		if ( $header['download_content_length'] != $this->size ) {
			Log::get()->addInfo(
				'Change of media file content length detected.',
				array( 'media_file_id' => $this->id, 'old_size' => $this->size, 'new_size' => $header['download_content_length'] )
			);
			do_action( 'podlove_media_file_content_has_changed', $this->id );
		}

		// check if mime type matches asset mime type
		$mime_type = $this->episode_asset()->file_type()->mime_type;
		if ( $header['content_type'] != $mime_type ) {
			Log::get()->addWarning(
				'Media file mime type does not match expected asset mime type.',
				array( 'media_file_id' => $this->id, 'mime_type' => $header['content_type'], 'expected_mime_type' => $mime_type )
			);
		}
	}

	/**
	 * @todo  use \Podlove\Http\Curl	
	 */
	public static function curl_get_header_for_url( $url, $etag = NULL ) {
		
		if ( ! function_exists( 'curl_exec' ) )
			return false;

		$curl = curl_init();
		$curl_version = curl_version();

		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ); // make curl_exec() return the result
		curl_setopt( $curl, CURLOPT_HEADER, true );         // header only
		curl_setopt( $curl, CURLOPT_NOBODY, true );         // return no body; HTTP request method: HEAD
		curl_setopt( $curl, CURLOPT_FAILONERROR, true );
		if ( $etag ) {
			curl_setopt( $curl, CURLOPT_HTTPHEADER, array(
				'If-None-Match: "' . $etag . '"'
			) );
		}
		if ( ini_get( 'open_basedir' ) == '' && ini_get( 'safe_mode' ) != '1' ) {
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true ); // follow redirects
			curl_setopt( $curl, CURLOPT_MAXREDIRS, 5 );         // maximum number of redirects
		}
		curl_setopt(
			$curl,
			CURLOPT_USERAGENT,
			sprintf(
				'PHP/%s (; ) cURL/%s(OpenSSL/%s; zlib/%s) Wordpress/%s (; ) %s/%s (; )',
				phpversion(),
				$curl_version['version'],
				$curl_version['ssl_version'],
				$curl_version['libz_version'],
				get_bloginfo( 'version' ),
				\Podlove\get_plugin_header( 'Name' ),
				\Podlove\get_plugin_header( 'Version' )
			)
		);
		
		$response = curl_exec( $curl );
		$response_header = curl_getinfo( $curl );
		curl_close( $curl );
		
		return array( 'header' => $response_header, 'response' => $response );
	}
	
}

MediaFile::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
MediaFile::property( 'episode_id', 'INT' );
MediaFile::property( 'episode_asset_id', 'INT' );
MediaFile::property( 'size', 'INT' );
MediaFile::property( 'etag', 'VARCHAR(255)' );