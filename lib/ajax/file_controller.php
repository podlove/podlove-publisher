<?php
namespace Podlove\AJAX;

use \Podlove\Model\Template;
use \Podlove\Model\MediaFile;

class FileController {

	public static function init() {

		$actions = array(
			'update', 'create'
		);

		foreach ( $actions as $action )
			add_action( 'wp_ajax_podlove-file-' . $action, array( __CLASS__, str_replace( '-', '_', $action ) ) );
	}

	public static function update() {
		$file_id = (int) $_REQUEST['file_id'];

		$file = MediaFile::find_by_id( $file_id );

		if ( isset( $_REQUEST['slug'] ) )
			self::simulate_temporary_episode_slug( $_REQUEST['slug'] );

		$info = $file->determine_file_size();
		$file->save();

		$result = array();
		$result['file_url']  = $file->get_file_url();
		$result['file_id']   = $file_id;
		$result['reachable'] = podlove_is_resolved_and_reachable_http_status( $info['http_code'] );
		$result['file_size'] = $file->size;
		$result['file_size_human'] = number_format_i18n($file->size);

		if ( ! $result['reachable'] ) {
			$info['certinfo'] = print_r($info['certinfo'], true);
			$info['php_open_basedir'] = ini_get( 'open_basedir' );
			$info['php_curl'] = in_array( 'curl', get_loaded_extensions() );
			$info['curl_exec'] = function_exists( 'curl_exec' );

			\Podlove\Log::get()->addError("Can't reach {$file->get_file_url()}", $info);
		} else {
			do_action( 'podlove_media_file_content_verified', $file->id );
		}

		Ajax::respond_with_json( $result );
	}

	public static function create() {

		$episode_id        = (int) $_REQUEST['episode_id'];
		$episode_asset_id  = (int) $_REQUEST['episode_asset_id'];

		if ( ! $episode_id || ! $episode_asset_id )
			die();

		if ( isset( $_REQUEST['slug'] ) )
			self::simulate_temporary_episode_slug( $_REQUEST['slug'] );

		$file = MediaFile::find_or_create_by_episode_id_and_episode_asset_id( $episode_id, $episode_asset_id );

		Ajax::respond_with_json( array(
			'file_id'   => $file->id,
			'file_size' => $file->size,
			'file_url'  => $file->get_file_url()
		) );
	}

	private static function simulate_temporary_episode_slug( $slug ) {
		add_filter( 'podlove_file_url_template', function ( $template ) use ( $slug ) {
			return str_replace( '%episode_slug%', \Podlove\prepare_episode_slug_for_url( $slug ), $template );;
		} );
	}
}
