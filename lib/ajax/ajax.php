<?php
namespace Podlove\AJAX;
use \Podlove\Model;

class Ajax {

	public function __construct() {

		// notes: 
		//  - all actions must be prefixed with "podlove-"
		//  - hyphens in actions are substituted for underscores in methods
		$actions = array(
			'get-new-guid',
			'validate-file',
			'validate-url',
			'update-file',
			'create-file',
			'create-episode',
			'update-asset-position'
		);

		foreach ( $actions as $action )
			add_action( 'wp_ajax_podlove-' . $action, array( $this, str_replace( '-', '_', $action ) ) );
	}

	private function respond_with_json( $result ) {
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
		header( 'Content-type: application/json' );
		echo json_encode( $result );
		die();
	}

	public function get_new_guid() {
		$post_id = $_REQUEST['post_id'];

		$post = get_post( $post_id );
		$guid = \Podlove\Custom_Guid::guid_for_post( $post );

		$this->respond_with_json( array( 'guid' => $guid ) );
	}

	public function validate_file() {
		$file_id = $_REQUEST['file_id'];

		$file = \Podlove\Model\MediaFile::find_by_id( $file_id );
		$info = $file->curl_get_header();

		$result = array();
		$result['file_id']   = $file_id;
		$result['reachable'] = ( $info['http_code'] >= 200 && $info['http_code'] < 300 );
		$result['file_size'] = $info['download_content_length'];

		$this->respond_with_json( $result );
	}

	public function validate_url() {
		$file_url = $_REQUEST['file_url'];

		$info = \Podlove\Model\MediaFile::curl_get_header_for_url( $file_url );

		$result = array();
		$result['file_url']  = $file_url;
		$result['reachable'] = ( $info['http_code'] >= 200 && $info['http_code'] < 300 );
		$result['file_size'] = $info['download_content_length'];

		$validation_cache = get_option( 'podlove_migration_validation_cache', array() );
		$validation_cache[ $file_url ] = $result['reachable'];
		update_option( 'podlove_migration_validation_cache', $validation_cache );

		$this->respond_with_json( $result );
	}

	public function update_file() {
		$file_id = $_REQUEST['file_id'];

		$file = \Podlove\Model\MediaFile::find_by_id( $file_id );

		if ( isset( $_REQUEST['slug'] ) ) {
			// simulate a not-saved-yet slug
			add_filter( 'podlove_file_url_template', function ( $template ) {
				return str_replace( '%episode_slug%', $_REQUEST['slug'], $template );;
			} );
		}

		$info = $file->determine_file_size();
		$file->save();

		$result = array();
		$result['file_id']   = $file_id;
		$result['reachable'] = ( $info['http_code'] >= 200 && $info['http_code'] < 300 );
		$result['file_size'] = $info['download_content_length'];

		if ( ! $result['reachable'] ) {
			unset( $info['certinfo'] );
			$info['php_open_basedir'] = ini_get( 'open_basedir' );
			$info['php_safe_mode'] = ini_get( 'safe_mode' );
			$info['php_curl'] = in_array( 'curl', get_loaded_extensions() );
			$info['curl_exec'] = function_exists( 'curl_exec' );
			$result['message'] = "--- # Can't reach {$file->get_file_url()}\n";
			$result['message'].= "--- # Please include this output when you report a bug\n";
			foreach ( $info as $key => $value ) {
				$result['message'] .= "$key: $value\n";
			}
		}

		$this->respond_with_json( $result );
	}

	public function create_file() {
		$episode_id        = $_REQUEST['episode_id'];
		$episode_asset_id  = $_REQUEST['episode_asset_id'];

		if ( ! $episode_id || ! $episode_asset_id )
			die();

		if ( isset( $_REQUEST['slug'] ) ) {
			// simulate a not-saved-yet slug
			add_filter( 'podlove_file_url_template', function ( $template ) {
				return str_replace( '%episode_slug%', $_REQUEST['slug'], $template );;
			} );
		}

		$file = Model\MediaFile::find_or_create_by_episode_id_and_episode_asset_id( $episode_id, $episode_asset_id );

		$result = array();
		$result['file_id']   = $file->id;
		$result['file_size'] = $file->size;

		$this->respond_with_json( $result );
	}

	public function create_episode() {

		$slug  = isset( $_REQUEST['slug'] )  ? $_REQUEST['slug']  : NULL;
		$title = isset( $_REQUEST['title'] ) ? $_REQUEST['title'] : NULL;

		if ( ! $slug || ! $title )
			die();

		$args = array(
			'post_type' => 'podcast',
			'post_title' => $title,
			'post_content' => \Podlove\Podcast_Post_Type::get_default_post_content()
		);

		// create post
		$post_id = wp_insert_post( $args );

		// link episode and release
		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );
		$episode->slug = $slug;
		$episode->enable = true;
		$episode->active = true;
		$episode->save();

		// activate all media files
		$episode_assets = Model\EpisodeAsset::all();
		foreach ( $episode_assets as $episode_asset ) {
			$media_file = new \Podlove\Model\MediaFile();
			$media_file->episode_id = $episode->id;
			$media_file->episode_asset_id = $episode_asset->id;
			$media_file->save();
		}

		// generate response
		$result = array();
		$result['post_id'] = $post_id;
		$result['post_edit_url'] = get_edit_post_link( $post_id );

		$this->respond_with_json( $result );
	}

	public function update_asset_position() {

		$asset_id = (int)   $_REQUEST['asset_id'];
		$position = (float) $_REQUEST['position'];

		if ( $asset = Model\EpisodeAsset::find_by_id( $asset_id ) ) {
			$asset->position = $position;
			$asset->save();
		}

		die();
	}
	
}

