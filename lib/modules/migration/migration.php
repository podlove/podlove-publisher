<?php
namespace Podlove\Modules\Migration;
use Podlove\Modules\Migration\Settings\Assistant;
use Podlove\Modules\Migration\Enclosure;
use Podlove\Modules\Migration\Legacy_Post_Parser;
use \Podlove\Model;

class Migration extends \Podlove\Modules\Base {

		protected $module_name = 'Migration';
		protected $module_description = 'Helps you migrate from PodPress/PowerPress/... to Podlove.';

		public function load() {
			add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_styles' ) );
			add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		}

		public function register_admin_styles() {
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'podlove_settings_migration_handle' ) {
				wp_register_script( 'twitter-bootstrap-script', $this->get_module_url() . '/js/bootstrap.min.js' );
				wp_enqueue_script( 'twitter-bootstrap-script', 'jquery' );

				wp_register_style( 'twitter-bootstrap-style', $this->get_module_url() . '/css/bootstrap.min.css' );
				wp_enqueue_style( 'twitter-bootstrap-style' );
			}
		}

		public function register_menu() {
			new Settings\Assistant( \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE );
		}
}

function get_podcast_settings() {

	$migration_settings = get_option( 'podlove_migration', array() );
	$migration_settings = ( isset( $migration_settings['podcast'] ) ) ? $migration_settings['podcast'] : array();

	$defaults = array(
		'title'    => get_bloginfo('name'),
		'subtitle' => get_bloginfo('description'),
		'summary'  => '',
		'media_file_base_url_option' => 'preset',
		'media_file_base_url_preset' => NULL,
		'media_file_base_url_custom' => ''
	);

	return wp_parse_args( $migration_settings, $defaults );
}

function get_media_file_base_url() {

	$podcast = get_podcast_settings();

	if ( isset( $podcast['media_file_base_url_option'] ) && $podcast['media_file_base_url_option'] == 'preset' )
		return $podcast['media_file_base_url_preset'];
	else
		return $podcast['media_file_base_url_custom'];
	
}

function migrate_post( $post_id ) {

	$post = get_post( $post_id );
	$migration_settings = get_option( 'podlove_migration', array() );

	$post_content = $post->post_content;

	if ( $migration_settings['cleanup']['player'] ) {
		$post_content = preg_replace( '/\[(powerpress|podloveaudio|podlovevideo|display_podcast)[^\]]*\]/', '', $post_content );
	}

	$new_post = array(
		'menu_order'     => $post->menu_order,
		'comment_status' => $post->comment_status,
		'ping_status'    => $post->ping_status,
		'post_author'    => $post->post_author,
		'post_content'   => $post_content,
		'post_excerpt'   => $post->post_excerpt,
		'post_mime_type' => $post->post_mime_type,
		'post_parent'    => $post_id,
		'post_password'  => $post->post_password,
		'post_status'    => 'pending',
		'post_title'     => $post->post_title,
		'post_type'      => 'podcast',
		'post_date'      => $post->post_date,
		'post_date_gmt'  => get_gmt_from_date( $post->post_date )
	);

	$new_slug = NULL;
	switch ( $migration_settings['post_slug'] ) {
		case 'wordpress':
			$new_slug = $post->post_name;
			break;
		case 'file':
			$new_slug = Assistant::get_file_slug( $post );
			break;
		case 'number':
			$new_slug = Assistant::get_number_slug( $post );
			break;
	}

	$override_slug = function( $data, $postarr ) use ( $new_slug ) {
		if ( $new_slug ) {
			$data['post_name'] = $new_slug;
		}
		return $data;
	};

	add_filter( 'wp_insert_post_data', $override_slug, 10, 2 );
	$new_post_id = wp_insert_post( $new_post );
	remove_filter( 'wp_insert_post_data', $override_slug, 10, 2 );

	$new_post = get_post( $new_post_id );

	// update guid
	update_post_meta( $new_post_id, '_podlove_guid', $post->guid );

	// add redirect from previous url
	add_post_meta( $new_post_id, 'podlove_alternate_url', get_permalink( $post_id ) );

	// migrate taxonomies
	$taxonomies = get_object_taxonomies( get_post_type( $post_id ) );

	foreach( $taxonomies AS $tax ) {
		$terms = wp_get_object_terms( $post_id, $tax );
		$term = array();
		foreach( $terms AS $t ) {
			$term[] = $t->slug;
		} 
		
		wp_set_object_terms( $new_post_id, $term, $tax );
	}

	$post_data = new Legacy_Post_Parser( $post_id );

	$episode = Model\Episode::find_or_create_by_post_id( $new_post_id );
	$episode->slug = Assistant::get_episode_slug( $post, $migration_settings['slug'] );
	$episode->duration = $post_data->get_duration();
	$episode->subtitle = $post_data->get_subtitle();
	$episode->summary = $post_data->get_summary();
	$episode->save();

	foreach ( Model\EpisodeAsset::all() as $asset ) {
		Model\MediaFile::find_or_create_by_episode_id_and_episode_asset_id( $episode->id, $asset->id );
	}

	// copy all meta
	$meta = get_post_meta( $post_id );
	foreach ( $meta as $key => $values ) {
		if ( $key != 'enclosure' || ! $migration_settings['cleanup']['enclosures'] ) {
			foreach ( $values as $value ) {
				add_post_meta( $new_post_id, $key, $value );
			}
		}
	}

	// copy all comments
	foreach ( get_comments( array( 'post_id' => $post_id ) ) as $comment ) {
		$comment->comment_post_ID = $new_post_id;
		wp_insert_comment( (array) $comment );
	}

	return $new_post_id;
}

function ajax_migrate_post() {

	$new_post_id = migrate_post( (int) $_REQUEST['post_id'] );

	$migration_cache = get_option( 'podlove_migrated_posts_cache', array() );
	$migration_cache[ (int) $_REQUEST['post_id'] ] = (int) $new_post_id;
	update_option( 'podlove_migrated_posts_cache', $migration_cache );

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
	echo json_encode( array( 'url' => get_edit_post_link( $new_post_id ) ) );

	die();
}
add_action( 'wp_ajax_podlove-migrate-post', '\Podlove\Modules\Migration\ajax_migrate_post' );