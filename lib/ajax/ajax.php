<?php
namespace Podlove\AJAX;
use \Podlove\Model;

class Ajax {

	/**
	 * Conventions: 
	 * - all actions must be prefixed with "podlove-"
	 * - hyphens in actions are substituted for underscores in methods
	 */
	public function __construct() {

		$actions = array(
			'get-new-guid',
			'validate-file',
			'validate-url',
			'update-file',
			'create-file',
			'update-asset-position',
			'update-feed-position',
			'podcast',
			'hide-teaser',
			'get-license-url',
			'get-license-name',
			'get-license-parameters-from-url'
		);

		foreach ( $actions as $action )
			add_action( 'wp_ajax_podlove-' . $action, array( $this, str_replace( '-', '_', $action ) ) );
	}

	public static function respond_with_json( $result ) {
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
		header( 'Content-type: application/json' );
		echo json_encode( $result );
		die();
	}

	private function simulate_temporary_episode_slug( $slug ) {
		add_filter( 'podlove_file_url_template', function ( $template ) use ( $slug ) {
			return str_replace( '%episode_slug%', \Podlove\slugify( $slug ), $template );;
		} );
	}

	public function podcast() {
		$podcast = Model\Podcast::get_instance();
		$podcast_data = array();
		foreach ( $podcast->property_names() as $property ) {
			$podcast_data[ $property ] = $podcast->$property;
		}
		
		self::respond_with_json( $podcast_data );
	}

	public function get_new_guid() {
		$post_id = $_REQUEST['post_id'];

		$post = get_post( $post_id );
		$guid = \Podlove\Custom_Guid::guid_for_post( $post );

		self::respond_with_json( array( 'guid' => $guid ) );
	}

	public function validate_file() {
		$file_id = $_REQUEST['file_id'];

		$file = \Podlove\Model\MediaFile::find_by_id( $file_id );
		$info = $file->curl_get_header();
		$reachable = $info['http_code'] >= 200 && $info['http_code'] < 300;

		self::respond_with_json( array(
			'file_url'	=> $file_url,
			'reachable'	=> $reachable,
			'file_size'	=> $info['download_content_length']
		) );
	}

	public function validate_url() {
		$file_url = $_REQUEST['file_url'];

		$info = \Podlove\Model\MediaFile::curl_get_header_for_url( $file_url );
		$header = $info['header'];
		$reachable = $header['http_code'] >= 200 && $header['http_code'] < 300;

		$validation_cache = get_option( 'podlove_migration_validation_cache', array() );
		$validation_cache[ $file_url ] = $reachable;
		update_option( 'podlove_migration_validation_cache', $validation_cache );

		self::respond_with_json( array(
			'file_url'	=> $file_url,
			'reachable'	=> $reachable,
			'file_size'	=> $header['download_content_length']
		) );
	}

	public function update_file() {
		$file_id = (int) $_REQUEST['file_id'];

		$file = \Podlove\Model\MediaFile::find_by_id( $file_id );

		if ( isset( $_REQUEST['slug'] ) )
			$this->simulate_temporary_episode_slug( $_REQUEST['slug'] );

		$info = $file->determine_file_size();
		$file->save();

		$result = array();
		$result['file_url']  = $file->get_file_url();
		$result['file_id']   = $file_id;
		$result['reachable'] = ( $info['http_code'] >= 200 && $info['http_code'] < 300 || $info['http_code'] == 304 );
		$result['file_size'] = ( $info['http_code'] == 304 ) ? $file->size : $info['download_content_length'];

		if ( ! $result['reachable'] ) {
			$info['certinfo'] = print_r($info['certinfo'], true);
			$info['php_open_basedir'] = ini_get( 'open_basedir' );
			$info['php_safe_mode'] = ini_get( 'safe_mode' );
			$info['php_curl'] = in_array( 'curl', get_loaded_extensions() );
			$info['curl_exec'] = function_exists( 'curl_exec' );
			$errorLog = "--- # Can't reach {$file->get_file_url()}\n";
			$errorLog.= "--- # Please include this output when you report a bug\n";
			foreach ( $info as $key => $value ) {
				$errorLog .= "$key: $value\n";
			}

			\Podlove\Log::get()->addError( $errorLog );
		}

		self::respond_with_json( $result );
	}

	public function create_file() {

		$episode_id        = (int) $_REQUEST['episode_id'];
		$episode_asset_id  = (int) $_REQUEST['episode_asset_id'];

		if ( ! $episode_id || ! $episode_asset_id )
			die();

		if ( isset( $_REQUEST['slug'] ) )
			$this->simulate_temporary_episode_slug( $_REQUEST['slug'] );

		$file = Model\MediaFile::find_or_create_by_episode_id_and_episode_asset_id( $episode_id, $episode_asset_id );

		self::respond_with_json( array(
			'file_id'   => $file->id,
			'file_size' => $file->size,
			'file_url'  => $file->get_file_url()
		) );
	}

	public function update_asset_position() {

		$asset_id = (int)   $_REQUEST['asset_id'];
		$position = (float) $_REQUEST['position'];

		Model\EpisodeAsset::find_by_id( $asset_id )
			->update_attributes( array( 'position' => $position ) );

		die();
	}

	public function update_feed_position() {

		$feed_id = (int)   $_REQUEST['feed_id'];
		$position = (float) $_REQUEST['position'];

		Model\Feed::find_by_id( $feed_id )
			->update_attributes( array( 'position' => $position ) );

		die();
	}

	public function hide_teaser() {
		update_option( '_podlove_hide_teaser', TRUE );
	}

	private function parse_get_parameter_into_url_array() {
		return array(
						'version'		 => '3.0',
						'modification'	 => $_REQUEST['modification'],
						'commercial_use' => $_REQUEST['commercial_use'],
						'jurisdiction'	 => $_REQUEST['jurisdiction']
					);
	}

	public function get_license_url() {
		self::respond_with_json( \Podlove\Model\License::get_url_from_license( self::parse_get_parameter_into_url_array() ) );
	}

	public function get_license_name() {
		self::respond_with_json( \Podlove\Model\License::get_name_from_license( self::parse_get_parameter_into_url_array() ) );
	}

	public function get_license_parameters_from_url() {
		self::respond_with_json( \Podlove\Model\License::get_license_from_url( $_REQUEST['url'] ) );
	}
	
}
