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
			array( 'name' => 'Auphonic Production Description', 'type' => 'metadata', 'mime_type' => 'application/json',  'extension' => 'json' ),
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
	$default_modules = array( 'podlove_web_player', 'open_graph', 'asset_validation', 'logging' );
	foreach ( $default_modules as $module ) {
		\Podlove\Modules\Base::activate( $module );
	}

	// set default expert settings
	$settings = get_option( 'podlove', array() );
	if ( $settings === array() ) {
		$settings = array(
			'merge_episodes'         => 'on',
			'hide_wp_feed_discovery' => 'off',
			'use_post_permastruct'   => 'on',
			'episode_archive'        => 'on',
			'episode_archive_slug'   => '/podcast/',
			'custom_episode_slug'    => '/podcast/%podcast%/'
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
	\Podlove\run_system_report();
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
	flush_rewrite_rules();
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
 * Checking "merge_episodes" allows to see episodes on the front page.
 */
add_filter( 'pre_get_posts', function ( $wp_query ) {

	if ( \Podlove\get_setting( 'merge_episodes' ) !== 'on' )
		return $wp_query;

	if ( is_home() && $wp_query->is_main_query() && ! isset( $wp_query->query_vars["post_type"] ) ) {
		$wp_query->set(
			'post_type',
			array_merge( (array) $wp_query->get( 'post_type' ), array( 'podcast' ) )
		);
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

// fire activation and deactivation hooks for modules
add_action( 'update_option_podlove_active_modules', function( $old_val, $new_val ) {
	$deactivated_module = current( array_keys( array_diff_assoc( $old_val, $new_val ) ) );
	$activated_module   = current( array_keys( array_diff_assoc( $new_val, $old_val ) ) );

	if ( $deactivated_module ) {
		Log::get()->addInfo( 'Deactivate module "' . $deactivated_module . '"' );
		do_action( 'podlove_module_was_deactivated', $deactivated_module );
		do_action( 'podlove_module_was_deactivated_' . $deactivated_module );
	} elseif ( $activated_module ) {
		Log::get()->addInfo( 'Activate module "' . $activated_module . '"' );

		// init module before firing hooks
		$class = Modules\Base::get_class_by_module_name( $activated_module );
		if ( class_exists( $class ) )
			$class::instance()->load();

		do_action( 'podlove_module_was_activated', $activated_module );
		do_action( 'podlove_module_was_activated_' . $activated_module );
	}
}, 10, 2 );

function show_critical_errors() {

	$errors = get_option( 'podlove_global_messages', array() );

	if ( ! isset( $errors['errors'] ) && ! isset( $errors['notices'] ) )
		return;

	if ( count( $errors['errors'] ) + count( $errors['notices'] ) === 0 )
		return;

	// if there are errors, always run the system report to see if they are gone
	run_system_report();
    ?>
    <div class="error">
        
    	<?php if ( isset( $errors['errors'] ) ): ?>
			<h3>
				<?php echo __( 'Critical Podlove Warnings', 'podlove' ) ?>
			</h3>
    		<ul>
    			<?php foreach ( $errors['errors'] as $error ): ?>
    				<li><?php echo $error ?></li>
    			<?php endforeach; ?>
    			<?php foreach ( $errors['notices'] as $error ): ?>
    				<li><?php echo $error ?></li>
    			<?php endforeach; ?>
    		</ul>
    	<?php endif; ?>

    </div>
    <?php
}
add_action( 'admin_notices', '\Podlove\show_critical_errors' );

/**
 * System Report needs to be run whenever a setting has changed that could effect something critical.
 */
function run_system_report() {
	$report = new SystemReport;
	$report->run();
}

add_action( 'update_option_permalink_structure', '\Podlove\run_system_report' );
add_action( 'update_option_podlove', '\Podlove\run_system_report' );

/**
 * Simple method to allow support for multiple urls per post.
 *
 * Add custom post meta 'podlove_alternate_url' with old url part to match.
 */
function override404() {
	global $wpdb, $wp_query;

	if ( is_admin() )
		return;

	// check for global redirects
	$parsed_request = parse_url($_SERVER['REQUEST_URI']);
	$parsed_request_url = $parsed_request['path'];
	if ( isset( $parsed_request['query'] ) )
		$parsed_request_url .= "?" . $parsed_request['query'];

	foreach ( \Podlove\get_setting( 'podlove_setting_redirect' ) as $redirect ) {

		if ( ! strlen( trim( $redirect['from'] ) ) || ! strlen( trim( $redirect['to'] ) ) )
			continue;

		$parsed_url = parse_url($redirect['from']);
		
		$parsed_redirect_url = $parsed_url['path'];
		if ( isset( $parsed_url['query'] ) )
			$parsed_redirect_url .= "?" . $parsed_url['query'];

		if ( untrailingslashit( $parsed_redirect_url ) === untrailingslashit( $parsed_request_url ) ) {
			status_header( 301 );
			$wp_query->is_404 = false;
			\wp_redirect( $redirect['to'], 301 );
			exit;
		}
	}

	if ( ! $wp_query->is_404 )
		return;

	// check for episode redirects
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

function autoinsert_templates_into_content( $content ) {

	if ( get_post_type() !== 'podcast' )
		return $content;

	foreach ( Model\Template::all() as $template ) {
		$shortcode = '[podlove-template id="' . $template->title . '"]';
		if ( stripos( $content, $shortcode ) === false ) {
			if ( $template->autoinsert == 'beginning' ) {
				$content = $shortcode . $content;
			} elseif ( $template->autoinsert == 'end' ) {
				$content = $content . $shortcode;
			}
		}
	}

	return $content;
}
add_filter( 'the_content', '\Podlove\autoinsert_templates_into_content' );


function podlove_and_wordpress_permastructs_are_equal() {

	if ( \Podlove\get_setting( 'use_post_permastruct' ) == 'on' )
		return true;

	return untrailingslashit( \Podlove\get_setting( 'custom_episode_slug' ) ) == untrailingslashit( str_replace( '%postname%', '%podcast%', get_option( 'permalink_structure' ) ) );
}

/**
 * Changes the permalink for a custom post type
 *
 * @uses $wp_rewrite
 */
function add_podcast_rewrite_rules() {
	global $wp_rewrite;
	
	// Get permalink structure
	$permastruct = \Podlove\get_setting( 'custom_episode_slug' );

	// Add rewrite tag
	$wp_rewrite->add_rewrite_tag( "%podcast%", '([^/]+)', "post_type=podcast&name=" );
	
	// Use same permastruct as post_type 'post'
	if ( podlove_and_wordpress_permastructs_are_equal() )
		$permastruct = str_replace( '%postname%', '%podcast%', get_option( 'permalink_structure' ) );

	// Enable generic rules for pages if permalink structure doesn't begin with a wildcard
	if ( "/%podcast%" == untrailingslashit( $permastruct ) ) {
		// Generate custom rewrite rules
		$wp_rewrite->matches = 'matches';
		$wp_rewrite->extra_rules = array_merge( $wp_rewrite->extra_rules, $wp_rewrite->generate_rewrite_rules( "%podcast%", EP_PERMALINK, true, true, false, true, true ) );
		$wp_rewrite->matches = '';
		
		// Add for WP_Query
		$wp_rewrite->use_verbose_page_rules = true;
	// Use standard mode
	} else {
		$wp_rewrite->add_permastruct( "podcast", $permastruct, false, EP_PERMALINK );
	}
	
	// Add archive pages
	if ( 'on' == \Podlove\get_setting( 'episode_archive' ) ) {
		$archive_slug = trim( \Podlove\get_setting( 'episode_archive_slug' ), '/' );

		$blog_prefix = \Podlove\get_blog_prefix();
		$blog_prefix = $blog_prefix ? trim( $blog_prefix, '/' ) . '/' : '';

		$wp_rewrite->add_rule( "{$blog_prefix}{$archive_slug}/?$", "index.php?post_type=podcast", 'top' );
		$wp_rewrite->add_rule( "{$blog_prefix}{$archive_slug}/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$", 'index.php?post_type=podcast&paged=$matches[1]', 'top' );
	}
}

/**
 * Filters the request query vars to search for posts with type 'post' and 'podcast'
 */
function podcast_permalink_proxy($query_vars) {
	// No post request
	if ( isset( $query_vars["preview"] ) || false == ( isset( $query_vars["name"] ) || isset( $query_vars["p"] ) ) )
		return $query_vars;
	
	if ( ! isset( $query_vars["post_type"] ) || $query_vars["post_type"] == "post" )
		$query_vars["post_type"] = array( "podcast", "post" );

	return $query_vars;
}

/**
 * Disable verbose page rules mode after startup
 *
 * @uses $wp_rewrite
 */
function no_verbose_page_rules() {
	global $wp_rewrite;
	$wp_rewrite->use_verbose_page_rules = false;
}

/**
 * Replace placeholders in permalinks with the correct values
 */
function generate_custom_post_link( $post_link, $id, $leavename = false, $sample = false ) {
	// Get post
	$post = &get_post($id);
	$draft_or_pending = isset( $post->post_status ) && in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) );
	
	// Sample
	if ( $sample )
		$post->post_name = "%pagename%";
	
	// Get permastruct
	$permastruct = \Podlove\get_setting( 'custom_episode_slug' );

	if ( podlove_and_wordpress_permastructs_are_equal() )
		$permastruct = str_replace( '%postname%', '%podcast%', get_option( 'permalink_structure' ) );
	
	// Only post_name in URL
	if ( "/%podcast%" == untrailingslashit( $permastruct ) && ( !$draft_or_pending || $sample ) )
		return home_url( user_trailingslashit( $post->post_name ) );
	
	//
	$unixtime = strtotime( $post->post_date );
	$post_link = str_replace( '%year%', date( 'Y', $unixtime ), $post_link );
	$post_link = str_replace( '%monthnum%', date( 'm', $unixtime ), $post_link );
	$post_link = str_replace( '%day%', date( 'd', $unixtime ), $post_link );
	$post_link = str_replace( '%hour%', date( 'H', $unixtime ), $post_link );
	$post_link = str_replace( '%minute%', date( 'i', $unixtime ), $post_link );
	$post_link = str_replace( '%second%', date( 's', $unixtime ), $post_link );
	$post_link = str_replace( '%post_id%', $post->ID, $post_link );
	$post_link = str_replace( '%podcast%', $post->post_name, $post_link );

	// category and author replacement copied from WordPress core
	if ( strpos($post_link, '%category%') !== false ) {

	$cats = get_the_category($post->ID);
	if ( $cats ) {
		usort($cats, '_usort_terms_by_ID'); // order by ID
		$category_object = apply_filters( 'post_link_category', $cats[0], $cats, $post );
		$category_object = get_term( $category_object, 'category' );
		$category = $category_object->slug;
		if ( $parent = $category_object->parent )
			$category = get_category_parents($parent, false, '/', true) . $category;
		}

		if ( empty($category) ) {
			$default_category = get_category( get_option( 'default_category' ) );
			$category = is_wp_error( $default_category ) ? '' : $default_category->slug;
		}

		$post_link = str_replace( '%category%', $category, $post_link );
	}

	if ( strpos($post_link, '%author%') !== false ) {
		$authordata = get_userdata($post->post_author);
		$post_link = str_replace( '%author%', $authordata->user_nicename, $post_link );
	}

	return $post_link;
}

if ( get_option( 'permalink_structure' ) != '' ) {
	add_action( 'after_setup_theme', '\Podlove\add_podcast_rewrite_rules', 99 );
	add_action( 'permalink_structure_changed', '\Podlove\add_podcast_rewrite_rules' );
	add_action( 'wp', '\Podlove\no_verbose_page_rules' );		
	add_filter( 'post_type_link', '\Podlove\generate_custom_post_link', 10, 4 );

	if ( podlove_and_wordpress_permastructs_are_equal() ) {
		add_filter( 'request', '\Podlove\podcast_permalink_proxy' );
	}
}

// devalidate caches when media file has changed
add_action( 'podlove_media_file_content_has_changed', function ( $media_file_id ) {
	if ( $media_file = Model\MediaFile::find_by_id( $media_file_id ) ) {
		$episode = $media_file->episode();
		delete_transient( 'podlove_chapters_string_' . $episode->id );
	}
} );

// enable chapters pages
add_action( 'wp', function() {

	if ( ! isset( $_REQUEST['chapters_format'] ) )
		return;

	if ( ! $episode = Model\Episode::find_or_create_by_post_id( get_the_ID() ) )
		return;

	header( "Content-Type: application/xml" );

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$chapters = new \Podlove\Feeds\Chapters( $episode );
	$chapters->render( 'inline' );

	exit;
} );

// register ajax actions
new \Podlove\AJAX\Ajax;
