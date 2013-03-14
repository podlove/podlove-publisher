<?php
namespace Podlove;

register_activation_hook(   PLUGIN_FILE, __NAMESPACE__ . '\activate' );
register_deactivation_hook( PLUGIN_FILE, __NAMESPACE__ . '\deactivate' );
register_uninstall_hook(    PLUGIN_FILE, __NAMESPACE__ . '\uninstall' );
add_action( 'wpmu_new_blog', '\Podlove\create_new_blog', 10, 6 );

function activate_for_current_blog() {
	Model\Feed::build();
	Model\FileType::build();
	Model\EpisodeAsset::build();
	Model\MediaFile::build();
	Model\Episode::build();
	Model\Template::build();

	if ( ! Model\FileType::has_entries() ) {
		$default_types = array(
			array( 'name' => 'MP3 Audio',              'type' => 'audio',    'mime_type' => 'audio/mpeg',  'extension' => 'mp3' ),
			array( 'name' => 'BitTorrent (MP3 Audio)', 'type' => 'audio',    'mime_type' => 'application/x-bittorrent',  'extension' => 'mp3.torrent' ),
			array( 'name' => 'MPEG-1 Video',           'type' => 'video',    'mime_type' => 'video/mpeg',  'extension' => 'mpg' ),
			array( 'name' => 'MPEG-4 AAC Audio',       'type' => 'audio',    'mime_type' => 'audio/mp4',   'extension' => 'm4a' ),
			array( 'name' => 'MPEG-4 ALAC Audio',      'type' => 'audio',    'mime_type' => 'audio/mp4',   'extension' => 'm4a' ),
			array( 'name' => 'MPEG-4 Video',           'type' => 'video',    'mime_type' => 'video/mp4',   'extension' => 'mp4' ),
			array( 'name' => 'M4V Video (Apple)',      'type' => 'video',    'mime_type' => 'video/x-m4v', 'extension' => 'm4v' ),
			array( 'name' => 'Ogg Vorbis Audio',       'type' => 'audio',    'mime_type' => 'audio/ogg',   'extension' => 'oga' ),
			array( 'name' => 'Ogg Vorbis Audio',       'type' => 'audio',    'mime_type' => 'audio/ogg',   'extension' => 'ogg' ),
			array( 'name' => 'Ogg Theora Video',       'type' => 'video',    'mime_type' => 'video/ogg',   'extension' => 'ogv' ),
			array( 'name' => 'WebM Audio',             'type' => 'audio',    'mime_type' => 'audio/webm',  'extension' => 'webm' ),
			array( 'name' => 'WebM Video',             'type' => 'video',    'mime_type' => 'video/webm',  'extension' => 'webm' ),
			array( 'name' => 'FLAC Audio',             'type' => 'audio',    'mime_type' => 'audio/flac',  'extension' => 'flac' ),
			array( 'name' => 'Opus Audio',             'type' => 'audio',    'mime_type' => 'audio/opus',  'extension' => 'opus' ),
			array( 'name' => 'Matroska Audio',         'type' => 'audio',    'mime_type' => 'audio/x-matroska',  'extension' => 'mka' ),
			array( 'name' => 'Matroska Video',         'type' => 'video',    'mime_type' => 'video/x-matroska',  'extension' => 'mkv' ),
			array( 'name' => 'Matroska Video',         'type' => 'video',    'mime_type' => 'video/x-matroska',  'extension' => 'mkv' ),
			array( 'name' => 'PDF Document',           'type' => 'ebook',    'mime_type' => 'application/pdf',  'extension' => 'pdf' ),
			array( 'name' => 'ePub Document',          'type' => 'ebook',    'mime_type' => 'application/epub+zip',  'extension' => 'epub' ),
			array( 'name' => 'PNG Image',              'type' => 'image',    'mime_type' => 'image/png',   'extension' => 'png' ),
			array( 'name' => 'JPEG Image',             'type' => 'image',    'mime_type' => 'image/jpeg',  'extension' => 'jpg' ),
			array( 'name' => 'mp4chaps Chapter File',  'type' => 'chapters', 'mime_type' => 'text/plain',  'extension' => 'chapters.txt' ),
			array( 'name' => 'Podlove Simple Chapters','type' => 'chapters', 'mime_type' => 'application/xml',  'extension' => 'psc' ),
			array( 'name' => 'Subrip Captions',        'type' => 'captions', 'mime_type' => 'application/x-subrip',  'extension' => 'srt' ),
			array( 'name' => 'WebVTT Captions',        'type' => 'captions', 'mime_type' => 'text/vtt',  'extension' => 'vtt' ),
		);
		
		foreach ( $default_types as $file_type ) {
			$f = new Model\FileType;
			foreach ( $file_type as $key => $value ) {
				$f->{$key} = $value;
			}
			$f->save();
		}
	}

	// set default modules
	$default_modules = array( 'podlove_web_player', 'open_graph', 'migration' );
	foreach ( $default_modules as $module ) {
		\Podlove\Modules\Base::activate( $module );
	}

	// set default expert settings
	$settings = get_option( 'podlove', array() );
	if ( $settings === array() ) {
		$settings = array(
			'merge_episodes'         => 'on',
			'hide_wp_feed_discovery' => 'off',
			'custom_episode_slug'    => ''
		);
		update_option( 'podlove', $settings );
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
			$blogids = $wpdb->get_col( "SELECT blog_id FROM " . $wpdb->blogs );
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

	set_transient( 'podlove_needs_to_flush_rewrite_rules', true );
}

/**
 * Hackish workaround to flush rewrite rules.
 *
 * flush_rewrite_rules() is expensive, so it should only be called once.
 * However, calling it on activaton doesn't work. So I add a temporary flag
 * and call it when the flag exists. Not pretty but it does the job.
 */
add_action( 'admin_init', function () {
	if ( delete_transient( 'podlove_needs_to_flush_rewrite_rules' ) )
		flush_rewrite_rules();
} );

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
			$blogids = $wpdb->get_col( "SELECT blog_id FROM " . $wpdb->blogs );
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
	Model\FileType::destroy();
	Model\EpisodeAsset::destroy();
	Model\MediaFile::destroy();
	Model\Episode::destroy();
	Model\Template::destroy();
}

add_action( 'admin_head', '\Podlove\custom_admin_icons' );

function custom_admin_icons() {
    ?>
    <style>

    	/* PODLOVE EPISODE ICON */

        /* Admin Menu - 16px */
        #menu-posts-podcast .wp-menu-image {
            background: url(<?php echo \Podlove\PLUGIN_URL ?>/images/episoden/icon-adminmenu16-sprite.png) no-repeat 6px 6px !important;
        }
		#menu-posts-podcast:hover .wp-menu-image, #menu-posts-podcast.wp-has-current-submenu .wp-menu-image {
            background-position: 6px -24px !important;
        }
        /* Post Screen - 32px */
        .icon32-posts-podcast {
        	background: url(<?php echo \Podlove\PLUGIN_URL ?>/images/episoden/icon-adminpage32.png) no-repeat 0px 0px !important;
        }
        @media
        only screen and (-webkit-min-device-pixel-ratio: 1.5),
        only screen and (   min--moz-device-pixel-ratio: 1.5),
        only screen and (     -o-min-device-pixel-ratio: 3/2),
        only screen and (        min-device-pixel-ratio: 1.5),
        only screen and (        		 min-resolution: 1.5dppx) {
        	
        	/* Admin Menu - 16px @2x */
        	#menu-posts-podcast .wp-menu-image {
        		background-image: url('<?php echo \Podlove\PLUGIN_URL ?>/images/episoden/icon-adminmenu16-sprite_2x.png') !important;
        		-webkit-background-size: 16px 48px;
        		-moz-background-size:    16px 48px;
        		background-size:         16px 48px;
        	}
        	/* Post Screen - 32px @2x */
        	.icon32-posts-podcast {
        		background-image: url('<?php echo \Podlove\PLUGIN_URL ?>/images/episoden/icon-adminpage32_2x.png') no-repeat center !important;
        		-webkit-background-size: 32px 32px !important;
        		-moz-background-size:    32px 32px !important;
        		background-size:         32px 32px !important;
        	}         
        }

        /* PODLOVE SETTINGS ICON */

        /* Admin Menu - 16px */
        #toplevel_page_podlove_settings_handle .wp-menu-image {
            background: url(<?php echo \Podlove\PLUGIN_URL ?>/images/podlove/icon-adminmenu16-sprite.png) no-repeat 6px 6px !important;
        }
		#toplevel_page_podlove_settings_handle:hover .wp-menu-image, #toplevel_page_podlove_settings_handle.wp-has-current-submenu .wp-menu-image {
            background-position: 6px -26px !important;
        }
        /* Post Screen - 32px */
        #icon-podlove-podcast {
        	background: url(<?php echo \Podlove\PLUGIN_URL ?>/images/podlove/icon-adminpage32.png) no-repeat 0px 0px !important;
        }
        @media
        only screen and (-webkit-min-device-pixel-ratio: 1.5),
        only screen and (   min--moz-device-pixel-ratio: 1.5),
        only screen and (     -o-min-device-pixel-ratio: 3/2),
        only screen and (        min-device-pixel-ratio: 1.5),
        only screen and (        		 min-resolution: 1.5dppx) {
        	
        	/* Admin Menu - 16px @2x */
        	#toplevel_page_podlove_settings_handle .wp-menu-image {
        		background-image: url('<?php echo \Podlove\PLUGIN_URL ?>/images/podlove/icon-adminmenu16-sprite_2x.png') !important;
        		-webkit-background-size: 16px 48px;
        		-moz-background-size:    16px 48px;
        		background-size:         16px 48px;
        	}
        	/* Post Screen - 32px @2x */
        	.icon-podlove-podcast {
        		background-image: url('<?php echo \Podlove\PLUGIN_URL ?>/images/podlove/icon-adminpage32_2x.png') no-repeat center !important;
        		-webkit-background-size: 32px 32px !important;
        		-moz-background-size:    32px 32px !important;
        		background-size:         32px 32px !important;
        	}         
        }
    </style>
<?php } 

/**
 * Activate internal modules.
 */
add_action( 'init', array( '\Podlove\Custom_Guid', 'init' ) );

/**
 * Adds feed discover links to WordPress head.
 *
 * @todo move into \Podlove\Feed_Discoverability and load like \Podlove\Custom_Guid
 */
function add_feed_discoverability() {

	if ( is_admin() )
		return;

	if ( ! function_exists( '\Podlove\Feeds\prepare_for_feed' ) )
		require_once \PODLOVE\PLUGIN_DIR . 'lib/feeds/base.php';

	$feeds = \Podlove\Model\Feed::find_all_by_discoverable( 1 );

	foreach ( $feeds as $feed ) {
		echo '<link rel="alternate" type="' . $feed->get_content_type() . '" title="' . \Podlove\Feeds\prepare_for_feed( $feed->title_for_discovery() ) . '" href="' . $feed->get_subscribe_url() . "\" />\n";			
	}
		
}

add_action( 'init', function () {
	new Podcast_Post_Type();

	// priority 2 so they are placed below the WordPress default discovery links
	add_action( 'wp_head', '\Podlove\add_feed_discoverability', 2 );

	// hide WordPress default link discovery
	if ( \Podlove\get_setting( 'hide_wp_feed_discovery' ) === 'on' ) {
		remove_action( 'wp_head', 'feed_links',       2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}
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

	/*
	// deactivated as it defeats the ability to use static pages as front page
	if ( $wp_query->get( 'page_id' ) == get_option( 'page_on_front' ) ) {
		$wp_query->set( 'post_type', array( 'podcast' ) );

		// fix conditional functions
		$wp_query->set( 'page_id', '' );
		$wp_query->is_page = 0;
		$wp_query->is_singular = 0;
	}
	*/

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

/**
 * Simple method to allow support for multiple urls per post.
 *
 * Add custom post meta 'podlove_alternate_url' with old url part to match.
 */
function override404() {
	global $wpdb, $wp_query;

	if ( ! $wp_query->is_404 )
		return;

	$rows = $wpdb->get_results( "
		SELECT
			post_id, meta_value url
		FROM
			" . $wpdb->postmeta . "
		WHERE
			meta_key = 'podlove_alternate_url'
	", ARRAY_A );

	foreach ( $rows as $row ) {
		if ( false !== stripos( $_SERVER['REQUEST_URI'], $row['url'] ) ) {
			status_header( 301 );
			$wp_query->is_404 = false;
			\wp_redirect( \get_permalink( $row['post_id'] ), 301 );
			exit;
		}
	}

}
add_filter( 'template_redirect', '\Podlove\override404' );


function clear_all_caches() {

	// clear WP Super Cache
	if ( function_exists( 'wp_cache_clear_cache' ) )
		wp_cache_clear_cache();
}

// Fix WordPress post meta import for our custom GUID
// The importer inserts a post, which creates a new GUID. Then it adds the
// post metas resulting in two GUID entries. Here we make sure to only use
// the previous one.
add_action( 'added_post_meta', function ( $meta_id, $post_id, $meta_key, $_meta_value ) {
	
	if ( $meta_key !== '_podlove_guid' )
		return;

	$metas = get_post_meta( $post_id, '_podlove_guid' );
	if ( count( $metas ) > 1 ) {
		foreach ( $metas as $meta ) {
			if ( $meta !== $_meta_value ) {
				delete_post_meta( $post_id, $meta_key, $meta );
			}
		}
	}

}, 10, 4 );

namespace Podlove\AJAX;
use \Podlove\Model;

function get_new_guid() {
	$post_id = $_REQUEST['post_id'];

	$post = get_post( $post_id );
	$guid = \Podlove\Custom_Guid::guid_for_post( $post );

	$result = array( 'guid' => $guid );

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
	echo json_encode($result);

	die();
}

add_action( 'wp_ajax_podlove-get-new-guid', '\Podlove\AJAX\get_new_guid' );

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
	echo json_encode( $result );

	die();
}

add_action( 'wp_ajax_podlove-validate-file', '\Podlove\AJAX\validate_file' );

function validate_url() {
	$file_url = $_REQUEST['file_url'];

	$info = \Podlove\Model\MediaFile::curl_get_header_for_url( $file_url );

	$result = array();
	$result['file_url']  = $file_url;
	$result['reachable'] = ( $info['http_code'] >= 200 && $info['http_code'] < 300 );
	$result['file_size'] = $info['download_content_length'];

	$validation_cache = get_option( 'podlove_migration_validation_cache', array() );
	$validation_cache[ $file_url ] = $result['reachable'];
	update_option( 'podlove_migration_validation_cache', $validation_cache );

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
	echo json_encode( $result );

	die();
}
add_action( 'wp_ajax_podlove-validate-url', '\Podlove\AJAX\validate_url' );

function update_file() {
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

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
	echo json_encode($result);

	die();
}
add_action( 'wp_ajax_podlove-update-file', '\Podlove\AJAX\update_file' );

function create_file() {
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

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
	echo json_encode($result);

	die();
}
add_action( 'wp_ajax_podlove-create-file', '\Podlove\AJAX\create_file' );

function create_episode() {

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

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
	echo json_encode( $result );

	die();
}
add_action( 'wp_ajax_podlove-create-episode', '\Podlove\AJAX\create_episode' );

function update_asset_position() {

	$asset_id = (int)   $_REQUEST['asset_id'];
	$position = (float) $_REQUEST['position'];

	$asset = Model\EpisodeAsset::find_by_id( $asset_id );
	if ( $asset ) {
		$asset->position = $position;
		$asset->save();
	}

	die();
}
add_action( 'wp_ajax_podlove-update-asset-position', '\Podlove\AJAX\update_asset_position' );
