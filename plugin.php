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

	$default_modules = array( 'podlove_web_player' );
	foreach ( $default_modules as $module ) {
		\Podlove\Modules\Base::activate( $module );
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
		if ( isset( $_GET['networkwide'] ) && ( $_GET['networkwide'] == 1 ) ) {
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
		if ( isset( $_GET['networkwide'] ) && ( $_GET['networkwide'] == 1 ) ) {
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

	foreach ( $feeds as $feed ) {
		if ( $feed->show() ) {
			echo '<link rel="alternate" type="' . $feed->get_content_type() . '" title="' . esc_attr( $feed->title ) . '" href="' . $feed->get_subscribe_url() . "\" />\n";			
		}
	}
		
}

add_action( 'init', function () {
	new Podcast_Post_Type();

	// priority 2 so they are placed below the WordPress default discovery links
	add_action( 'wp_head', '\Podlove\add_feed_discoverability', 2 );
});

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

// init modules
add_action( 'plugins_loaded', function () {
	$modules = Modules\Base::get_active_module_names();

	if ( empty( $modules ) )
		return;

	foreach ( $modules as $module_name ) {
		$class = Modules\Base::get_class_by_module_name( $module_name );
		if ( class_exists( $class ) ) {
			$class::instance()->load();
		} else {
			Modules\Base::deactivate( $module_name );
			add_action( 'admin_notices', function () use ( $module_name ) {
				?>
				<div id="message" class="error">
					<p>
						<strong><?php echo __( 'Warning' ) ?></strong>
						<?php echo sprintf( __( 'Podlove Module "%s" could not be found and has been deactivated.', 'podlove' ), $module_name ); ?>
					</p>
				</div>
				<?php
			} );
		}
	}
} );

namespace Podlove\AJAX;

function validate_file() {
	$file_id = $_REQUEST['file_id'];

	$file = \Podlove\Model\MediaFile::find_by_id( $file_id );
	$info = $file->curl_get_header();

	$result = array();
	$result['file_id']   = $file_id;
	$result['reachable'] = ( $info['http_code'] >= 200 && $info['http_code'] < 300 );
	$result['file_size'] = $info['download_content_length'];

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
	echo json_encode($result);

	die();
}

add_action( 'wp_ajax_podlove-validate-file', '\Podlove\AJAX\validate_file' );

function create_episode() {

	$show_id = isset( $_REQUEST['show_id'] ) ? $_REQUEST['show_id'] : NULL;
	$slug    = isset( $_REQUEST['slug'] )    ? $_REQUEST['slug']    : NULL;
	$title   = isset( $_REQUEST['title'] )   ? $_REQUEST['title']   : NULL;

	if ( ! $show_id || ! $slug || ! $title )
		die();


	$args = array(
		'post_type' => 'podcast',
		'post_title' => $title,
	);

	// create post
	$post_id = wp_insert_post( $args );

	// link episode and release
	$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );
	$release = \Podlove\Model\Release::find_or_create_by_episode_id_and_show_id( $episode->id, $show_id );
	$release->slug = $slug;
	$release->save();

	// activate all media files
	$show = \Podlove\Model\Show::find_by_id( $show_id );
	$media_locations = $show->valid_media_locations();
	foreach ( $media_locations as $media_location ) {
		$media_file = new \Podlove\Model\MediaFile();
		$media_file->release_id = $release->id;
		$media_file->media_location_id = $media_location->id;
		$media_file->save();
	}

	// generate response
	$result = array();
	$result['post_id'] = $post_id;
	$result['post_edit_url'] = get_edit_post_link( $post_id );

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
	echo json_encode($result);

	die();
}
add_action( 'wp_ajax_podlove-create-episode', '\Podlove\AJAX\create_episode' );
