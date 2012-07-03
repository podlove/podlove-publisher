<?php
namespace Podlove;

register_activation_hook(   PLUGIN_FILE, __NAMESPACE__ . '\activate' );
register_deactivation_hook( PLUGIN_FILE, __NAMESPACE__ . '\deactivate' );
register_uninstall_hook(    PLUGIN_FILE, __NAMESPACE__ . '\uninstall' );
add_action( 'wpmu_new_blog', '\Podlove\create_new_blog', 10, 6 );

function activate_for_current_blog() {
	Model\Feed::build();
	Model\MediaFormat::build();
	Model\MediaLocation::build();
	Model\MediaFile::build();
	Model\Show::build();
	Model\Episode::build();
	Model\Release::build();
	
	if ( ! Model\MediaFormat::has_entries() ) {
		$default_formats = array(
			array( 'name' => 'MP3 Audio',              'type' => 'audio', 'mime_type' => 'audio/mpeg',  'extension' => 'mp3' ),
			array( 'name' => 'BitTorrent (MP3 Audio)', 'type' => 'audio', 'mime_type' => 'application/x-bittorrent',  'extension' => 'mp3.torrent' ),
			array( 'name' => 'MPEG-1 Video',           'type' => 'video', 'mime_type' => 'video/mpeg',  'extension' => 'mpg' ),
			array( 'name' => 'MPEG-4 Audio',           'type' => 'audio', 'mime_type' => 'audio/mp4',   'extension' => 'm4a' ),
			array( 'name' => 'MPEG-4 Video',           'type' => 'video', 'mime_type' => 'video/mp4',   'extension' => 'm4v' ),
			array( 'name' => 'Ogg Vorbis Audio',       'type' => 'audio', 'mime_type' => 'audio/ogg',   'extension' => 'oga' ),
			array( 'name' => 'Ogg Theora Video',       'type' => 'video', 'mime_type' => 'video/ogg',   'extension' => 'ogv' ),
			array( 'name' => 'WebM Audio',             'type' => 'audio', 'mime_type' => 'audio/webm',  'extension' => 'webm' ),
			array( 'name' => 'WebM Video',             'type' => 'video', 'mime_type' => 'video/webm',  'extension' => 'webm' ),
			array( 'name' => 'Matroska Audio',         'type' => 'audio', 'mime_type' => 'audio/x-matroska',  'extension' => 'mka' ),
			array( 'name' => 'Matroska Video',         'type' => 'video', 'mime_type' => 'video/x-matroska',  'extension' => 'mkv' ),
		);
		// todo: add flac
		// todo: pentabarf-ish validation. summary of all conflicts / missing info is dashboard
		
		foreach ( $default_formats as $format ) {
			$f = new Model\MediaFormat;
			foreach ( $format as $key => $value ) {
				$f->{$key} = $value;
			}
			$f->save();
		}
	}	                    
}

/**
 * Hook: Create a new blog in a multisite environment.
 * 
 * When a new blog is created, we have to trigger the activation function
 * for in the scope of that blog.
 */
function create_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	global $wpdb;
	
	// something like 'podlove/podlove.php'
	$plugin_file = basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ );
    
	if ( is_plugin_active_for_network( $plugin_file ) ) {
		$current_blog = $wpdb->blogid;
		switch_to_blog( $blog_id );
		activate_for_current_blog();
		switch_to_blog( $current_blog );
	}
}

/**
 * Hook: Activate the plugin.
 * 
 * In a single blog install, just call activate_for_current_blog().
 * However, in a multisite install, iterate over all blogs and call the activate
 * function for each of them.
 */
function activate() {
	global $wpdb;
	
	if ( is_multisite() ) {
		if ( isset( $_GET[ 'networkwide' ] ) && ( $_GET[ 'networkwide' ] == 1 ) ) {
            		$current_blog = $wpdb->blogid;
			$blogids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM " . $wpdb->blogs ) );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog($blog_id);
				activate_for_current_blog();
			}
			switch_to_blog($current_blog);
		} else {
			activate_for_current_blog();
		}
	} else {
		activate_for_current_blog();
	}
	
}

function deactivate() {

}

/**
 * Hook: Uninstall the plugin.
 * 
 * In a single blog install, just call uninstall_for_current_blog().
 * However, in a multisite install, iterate over all blogs and call the 
 * uninstall function for each of them.
 */
function uninstall() {
	global $wpdb;
	
	if ( is_multisite() ) {
		if ( isset( $_GET[ 'networkwide' ] ) && ( $_GET[ 'networkwide' ] == 1 ) ) {
            		$current_blog = $wpdb->blogid;
			$blogids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM " . $wpdb->blogs ) );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog($blog_id);
				uninstall_for_current_blog();
			}
			switch_to_blog($current_blog);
		} else {
			activate_for_current_blog();
		}
	} else {
		uninstall_for_current_blog();
	}
}

function uninstall_for_current_blog() {
	Model\Feed::destroy();
	Model\MediaFormat::destroy();
	Model\MediaLocation::destroy();
	Model\MediaFile::destroy();
	Model\Show::destroy();
	Model\Episode::destroy();
	Model\Release::destroy();
}

/**
 * Adds feed discover links to WordPress head.
 *
 * @todo find a better place for this function
 */
function add_feed_discoverability() {

	if ( is_admin() )
		return;

	$feeds = \Podlove\Model\Feed::find_all_by_discoverable( 1 );

	foreach ( $feeds as $feed )
		echo '<link rel="alternate" type="' . $feed->get_content_type() . '" title="' . esc_attr( $feed->title ) . '" href="' . $feed->get_subscribe_url() . "\" />\n";	
}

add_action( 'init', function () {
	new Podcast_Post_Type();

	// priority 2 so they are placed below the WordPress default discovery links
	add_action( 'wp_head', '\Podlove\add_feed_discoverability', 2 );
});

// "activate" podlove-web-player plugin
// Not an ideal solution as it does not fire activation/deactivation hooks.
add_action( 'plugins_loaded', function () {

	if ( defined( 'MEDIAELEMENTJS_DIR' ) ) {
		define( 'PODLOVE_MEDIA_PLAYER', 'external' );
		return;
	}

	define( 'PODLOVE_MEDIA_PLAYER', 'internal' );

	$mediaplayer_plugin_file = PLUGIN_DIR . 'lib'
	                         . DIRECTORY_SEPARATOR . 'submodules'
	                         . DIRECTORY_SEPARATOR . 'webplayer'
	                         . DIRECTORY_SEPARATOR . 'podlove-web-player'
	                         . DIRECTORY_SEPARATOR . 'podlove-web-player.php';

	if ( file_exists( $mediaplayer_plugin_file) )
		require_once $mediaplayer_plugin_file;
} );

add_action( 'init', function () {

		if ( is_admin() )
			return;

	    wp_register_style(
	    	'podlove-frontend-css',
			plugins_url( 'css/frontend.css', __FILE__ ),
			array(),
			'1.0'
	    );

	    wp_enqueue_style( 'podlove-frontend-css' );
} );

// apply domain mapping plugin where it's essential
add_action( 'plugins_loaded', function () {
	if ( function_exists( 'domain_mapping_post_content' ) ) {
		add_filter( 'feed_link', 'domain_mapping_post_content', 20 );
		add_filter( 'podlove_subscribe_url', 'domain_mapping_post_content', 20 );
	}
} );

/**
 * This helps to get your blog tidy.
 * It's all about "Settings > Reading > Front page displays"
 *
 * Default: Check "Your latest posts" and we won't change anything.
 * However, if you check "A static page", we assume you'd like to separate
 * blog and podcast by moving your blog away and the podcast directory to "/".
 * That's what we do here.
 *
 * It's magic. Okay, I should probably document this publicly at some point.
 */
add_filter( 'pre_get_posts', function ( $wp_query ) {

	if ( is_home() && $wp_query->is_main_query() && \Podlove\get_setting( 'merge_episodes' ) === 'on' ) {
		$wp_query->set( 'post_type', array( 'post', 'podcast' ) );
		return $wp_query;
	}

	if ( get_option( 'show_on_front' ) === 'posts' )
		return $wp_query;

	if ( $wp_query->get( 'page_id' ) == get_option( 'page_on_front' ) ) {
		$wp_query->set( 'post_type', array( 'podcast' ) );

		// fix conditional functions
		$wp_query->set( 'page_id', '' );
		$wp_query->is_page = 0;
		$wp_query->is_singular = 0;
	}

	return $wp_query;
} );



namespace Podlove\AJAX;

function validate_file() {
	$file_id = $_REQUEST[ 'file_id' ];

	$file = \Podlove\Model\MediaFile::find_by_id( $file_id );
	$info = $file->curl_get_header();

	$result = array();
	$result[ 'file_id' ]   = $file_id;
	$result[ 'reachable' ] = ( $info[ 'http_code' ] >= 200 && $info[ 'http_code' ] < 300 );
	$result[ 'file_size' ] = $info[ 'download_content_length' ];

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
	echo json_encode($result);

	die();
}

add_action( 'wp_ajax_podlove-validate-file', '\Podlove\AJAX\validate_file' );
