<?php
namespace Podlove;

use Leth\IPAddress\IP, Leth\IPAddress\IPv4, Leth\IPAddress\IPv6;

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
	Model\DownloadIntent::build();
	Model\UserAgent::build();
	Model\GeoArea::build();
	Model\GeoAreaName::build();

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
			array( 'name' => 'Opus Audio',             'type' => 'audio',    'mime_type' => 'audio/ogg;codecs=opus',  'extension' => 'opus' ),
			array( 'name' => 'Matroska Audio',         'type' => 'audio',    'mime_type' => 'audio/x-matroska',  'extension' => 'mka' ),
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

	$podcast = Model\Podcast::get_instance();
	if (!$podcast->limit_items) {
		$podcast->limit_items = Model\Feed::ITEMS_NO_LIMIT;
	}
	$podcast->save();

	// set default modules
	$default_modules = array( 'podlove_web_player', 'open_graph', 'asset_validation', 'logging', 'oembed', 'feed_validation', 'import_export' );
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

	// set default template
	if (!$template = Model\Template::find_one_by_property('title', 'default')) {
		$template = new Model\Template;
		$template->title = 'default';
		$template->content = <<<EOT
{{ episode.player }}
[podlove-episode-downloads]
EOT;
		$template->save();

		$assignment = Model\TemplateAssignment::get_instance();
		$assignment->top = $template->id;
		$assignment->save();
	}
}

/**
 * Hook: Create a new blog in a multisite environment.
 * 
 * When a new blog is created, we have to trigger the activation function
 * for in the scope of that blog.
 */
function create_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	switch_to_blog( $blog_id );

	$plugin_file = "podlove/podlove.php";

	if ( is_plugin_active_for_network( $plugin_file ) ) {
		activate_for_current_blog();
	}

	restore_current_blog();
}

/**
 * Hook: Activate the plugin.
 * 
 * In a single blog install, just call activate_for_current_blog().
 * However, in a multisite install, iterate over all blogs and call the activate
 * function for each of them.
 */
function activate($network_wide) {
	global $wpdb;

	if ( $network_wide ) {
		set_time_limit(0); // may take a while, depending on network size
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
	Model\DownloadIntent::destroy();
	Model\UserAgent::destroy();
	Model\GeoArea::destroy();
	Model\GeoAreaName::destroy();
}

/**
 * Activate internal modules.
 */
add_action( 'init', array( '\Podlove\Custom_Guid', 'init' ) );
add_action( 'init', array( '\Podlove\Geo_Ip', 'init' ) );

// init cache (after plugins_loaded, so modules have a chance to hook)
add_action( 'init', array( '\Podlove\Cache\TemplateCache', 'get_instance' ) );

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

	$feeds = \Podlove\Model\Feed::all( 'ORDER BY position ASC' );

	foreach ( $feeds as $feed ) {
		if ( $feed->discoverable )
			echo '<link rel="alternate" type="' . $feed->get_content_type() . '" title="' . \Podlove\Feeds\prepare_for_feed( $feed->title_for_discovery() ) . '" href="' . $feed->get_subscribe_url() . "\" />\n";			
	}
		
}

add_action( 'init', function () {
	new Podcast_Post_Type();

	// priority 2 so they are placed below the WordPress default discovery links
	add_action( 'wp_head', '\Podlove\add_feed_discoverability', 2 );

	// hide WordPress default link discovery
	if ( \Podlove\get_setting( 'website', 'hide_wp_feed_discovery' ) === 'on' ) {
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

	    wp_register_style( 'podlove-admin-font', \Podlove\PLUGIN_URL . '/css/admin-font.css', array(), \Podlove\get_plugin_header( 'Version' ) );
	    wp_enqueue_style( 'podlove-admin-font' );
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

	if ( \Podlove\get_setting( 'website', 'merge_episodes' ) !== 'on' )
		return $wp_query;

	if ( is_home() && $wp_query->is_main_query() && ! isset( $wp_query->query_vars["post_type"] ) ) {
		$wp_query->set(
			'post_type',
			array_merge( array( 'post', 'podcast' ), (array) $wp_query->get( 'post_type' ) )
		);
	}

	return $wp_query;
} );

/**
 * Checking "merge_episodes" also includes episodes in main feed
 */
add_filter( 'request', function($query_var) {

	if ( !isset( $query_var['feed'] ) ) 
		return $query_var;
	
	if ( \Podlove\get_setting( 'website', 'merge_episodes' ) !== 'on' )
		return $query_var;
	
	$extend = array(
		'post' => 'post',
		'podcast' => 'podcast'
	);

	if ( empty( $query_var['post_type'] ) || ! is_array( $query_var['post_type'] ) ) {
		$query_var['post_type'] = $extend;
	} else {
		$query_var['post_type'] = array_merge( $query_var['post_type'], $extend );
	}
	
	return $query_var;
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
	$deactivated_modules = array_keys( array_diff_assoc( $old_val, $new_val ) );
	$activated_modules   = array_keys( array_diff_assoc( $new_val, $old_val ) );

	if ( $deactivated_modules ) {
		foreach ($deactivated_modules as $deactivated_module) {
			Log::get()->addInfo( 'Deactivate module "' . $deactivated_module . '"' );
			do_action( 'podlove_module_was_deactivated', $deactivated_module );
			do_action( 'podlove_module_was_deactivated_' . $deactivated_module );
		}
	} 

	if ( $activated_modules ) {
		foreach ($activated_modules as $activated_module) {
			Log::get()->addInfo( 'Activate module "' . $activated_module . '"' );

			// init module before firing hooks
			$class = Modules\Base::get_class_by_module_name( $activated_module );
			if ( class_exists( $class ) )
				$class::instance()->load();

			do_action( 'podlove_module_was_activated', $activated_module );
			do_action( 'podlove_module_was_activated_' . $activated_module );
		}
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

	foreach ( \Podlove\get_setting( 'redirects', 'podlove_setting_redirect' ) as $redirect ) {

		if ( ! strlen( trim( $redirect['from'] ) ) || ! strlen( trim( $redirect['to'] ) ) )
			continue;

		$parsed_url = parse_url($redirect['from']);
		
		$parsed_redirect_url = $parsed_url['path'];
		if ( isset( $parsed_url['query'] ) )
			$parsed_redirect_url .= "?" . $parsed_url['query'];

		if ( untrailingslashit( $parsed_redirect_url ) === untrailingslashit( $parsed_request_url ) ) {
			
			if ($redirect['code']) {
				$http_code = (int) $redirect['code'];
			} else {
				$http_code = 301; // default to permanent
			}

			// fallback for HTTP/1.0 clients
			if ($http_code == 307 && $_SERVER['SERVER_PROTOCOL'] == "HTTP/1.0") {
				$http_code = 302;
			}

			status_header( $http_code );
			$wp_query->is_404 = false;
			\wp_redirect( $redirect['to'], $http_code );
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

	$request_uri = untrailingslashit( $_SERVER['REQUEST_URI'] );
	foreach ( $rows as $row ) {
		if ( false !== stripos( $row['url'], $request_uri ) ) {
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

	if ( get_post_type() !== 'podcast' || post_password_required() )
		return $content;

	$template_assignments = Model\TemplateAssignment::get_instance();

	if ( $template_assignments->top ) {
		$shortcode = '[podlove-template id="' . Model\Template::find_by_id( $template_assignments->top )->title . '"]';
		if ( stripos( $content, $shortcode ) === false ) {
			$content = $shortcode . $content;
		}
	}

	if ( $template_assignments->bottom ) {
		$shortcode = '[podlove-template id="' . Model\Template::find_by_id( $template_assignments->bottom )->title . '"]';
		if ( stripos( $content, $shortcode ) === false ) {
			$content = $content . $shortcode;
		}
	}

	return $content;
}
add_filter( 'the_content', '\Podlove\autoinsert_templates_into_content' );


function podlove_and_wordpress_permastructs_are_equal() {

	if ( \Podlove\get_setting( 'website', 'use_post_permastruct' ) == 'on' )
		return true;

	return untrailingslashit( \Podlove\get_setting( 'website', 'custom_episode_slug' ) ) == untrailingslashit( str_replace( '%postname%', '%podcast%', get_option( 'permalink_structure' ) ) );
}

/**
 * Changes the permalink for a custom post type
 *
 * @uses $wp_rewrite
 */
function add_podcast_rewrite_rules() {
	global $wp_rewrite;
	
	// Get permalink structure
	$permastruct = \Podlove\get_setting( 'website', 'custom_episode_slug' );

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
	if ( 'on' == \Podlove\get_setting( 'website', 'episode_archive' ) ) {
		$archive_slug = trim( \Podlove\get_setting( 'website', 'episode_archive_slug' ), '/' );

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
	global $wpdb;

	// Previews default to post type "post" which is unfortunate.
	// However, when there is a name, we can determine the post_type anyway.
	// I don't think this is 100% bulletproof but seems to work well enough.
	if ( isset( $query_vars["preview"] ) && ! isset( $query_vars["post_type"] ) && isset( $query_vars["name"] ) ) {
		$query_vars["post_type"] = $wpdb->get_var(
			sprintf(
				'SELECT post_type FROM %s WHERE post_name = "%s"',
				$wpdb->posts,
				$wpdb->escape( $query_vars['name'] )
			)
		);
	}

	// No post request
	if ( isset( $query_vars["preview"] ) || false == ( isset( $query_vars["name"] ) || isset( $query_vars["p"] ) ) )
		return $query_vars;
	
	if ( ! isset( $query_vars["post_type"] ) || $query_vars["post_type"] == "post" )
		$query_vars["post_type"] = array( "podcast", "post" );

	return $query_vars;
}

/**
 * Filters trashed, imported posts from our posts out
 */
function remove_trash_posts_from_the_posts($posts, $wp_query) {
	global $wp_the_query;

	// Apply filter not in the backend and only on the main query
	if ( $wp_query->is_admin && $wp_the_query == $wp_query )
		return $posts;

	// No post request
	if ( isset( $wp_query->query["preview"] ) || false == ( isset( $wp_query->query["name"] ) || isset( $wp_query->query["p"] ) ) )
		return $posts;

	// Only check if we found more than 2 posts
	if ( 2 > count( $posts ) )
		return $posts;

	// Remove trashed posts
	foreach ( $posts as $index => $post ) {
		if ( 'trash' == $post->post_status ) {
			unset( $posts[$index] );
		}
	}

	// Resets array keys
	$posts = array_values( $posts );
	
	return $posts;
}
add_filter('posts_results', '\Podlove\remove_trash_posts_from_the_posts', 10, 2);

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
	$post = get_post($id);

	// only change Podlove URLs
	if ( $post->post_type != 'podcast' )
		return $post_link;

	$draft_or_pending = isset( $post->post_status ) && in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) );

	// Sample
	if ( $sample )
		$post->post_name = "%pagename%";
	
	// Get permastruct
	$permastruct = \Podlove\get_setting( 'website', 'custom_episode_slug' );

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
	if ( $media_file = Model\MediaFile::find_by_id( $media_file_id ) )
		if ( $episode = $media_file->episode() )
			$episode->delete_caches();
} );

add_action( 'podlove_episode_content_has_changed', function( $episode_id ) {
	if ( $episode = Model\Episode::find_by_id( $episode_id ) )
		$episode->delete_caches();
} );

// enable chapters pages
add_action( 'wp', function() {

	if ( ! is_single() )
		return;

	if ( ! isset( $_REQUEST['chapters_format'] ) )
		return;

	if ( ! $episode = Model\Episode::find_or_create_by_post_id( get_the_ID() ) )
		return;

	if ( ! in_array( $_REQUEST['chapters_format'], array( 'psc', 'json', 'mp4chaps' ) ) )
		$_REQUEST['chapters_format'] = 'psc';

	switch ( $_REQUEST['chapters_format'] ) {
		case 'psc':
			header( "Content-Type: application/xml" );
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			break;
		case 'mp4chaps':
			header( "Content-Type: text/plain" );
			break;
		case 'json':
			header( "Content-Type: application/json" );
			break;
	}	
	
	echo $episode->get_chapters( $_REQUEST['chapters_format'] );
	exit;
} );

/**
 * When changing from an external chapter asset to 'manual', copy external 
 * contents into local field.
 */
add_filter('pre_update_option_podlove_asset_assignment', function($new, $old) {
	global $wpdb;

	if (!isset($old['chapters']) || !isset($new['chapters']))
		return $new;

	if ($new['chapters'] != 'manual')  // just changes to manual
		return $new;

	if (((int) $old['chapters']) <= 0) // just changes from an asset
		return $new;

	$episodes = Model\Episode::allByTime();

	// 10 seconds per episode or 30 seconds since 1 request per asset 
	// is required if it is not cached
	set_time_limit(max(30, count($episodes) * 10));

	foreach ($episodes as $episode) {
		if ($chapters = $episode->get_chapters('mp4chaps'))
			$episode->update_attribute('chapters', mysql_real_escape_string($chapters));
	}

	// delete chapters caches
	$wpdb->query('DELETE FROM `' . $wpdb->options . '` WHERE option_name LIKE "%podlove_chapters_string_%"');

	return $new;
}, 10, 2);

function handle_media_file_download() {

	if (isset($_GET['download_media_file'])) {
		$download_media_file = $_GET['download_media_file'];
	} else {
		$download_media_file = get_query_var("download_media_file");
	}

	if (isset($_REQUEST['ptm_source'])) {
		$ptm_source = $_REQUEST['ptm_source'];
	} else {
		$ptm_source = get_query_var("ptm_source");
	}

	if (isset($_REQUEST['ptm_context'])) {
		$ptm_context = $_REQUEST['ptm_context'];
	} else {
		$ptm_context = get_query_var("ptm_context");
	}

	$download_media_file = (int) $download_media_file;
	$ptm_source  = trim($ptm_source);
	$ptm_context = trim($ptm_context);

	if (!$download_media_file)
		return;

	// tell WP Super Cache to not cache download links
	if ( ! defined( 'DONOTCACHEPAGE' ) )
		define( 'DONOTCACHEPAGE', true );

	// FIXME: this is a hack for bitlove => so move it in this module AND make sure the location in valid
	// if download_media_file is a URL, download directly
	if ( filter_var( $download_media_file, FILTER_VALIDATE_URL ) ) {
		$parsed_url = parse_url($download_media_file);
		$file_name = substr( $parsed_url['path'], strrpos( $parsed_url['path'], "/" ) + 1 );
		header( "Expires: 0" );
		header( 'Cache-Control: must-revalidate' );
	    header( 'Pragma: public' );
		header( "Content-Type: application/x-bittorrent" );
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=$file_name" );
		header( "Content-Transfer-Encoding: binary" );
		ob_clean();
		flush();
		while ( @ob_end_flush() ); // flush and end all output buffers
		readfile( $download_media_file );
		exit;
	}

	$media_file_id = $download_media_file;
	$media_file    = Model\MediaFile::find_by_id( $media_file_id );

	if ( ! $media_file ) {
		status_header( 404 );
		exit;
	}

	$episode_asset = $media_file->episode_asset();

	if ( ! $episode_asset || ! $episode_asset->downloadable ) {
		status_header( 404 );
		exit;
	}

	if (\Podlove\get_setting('tracking', 'mode') === "ptm_analytics") {
		$intent = new Model\DownloadIntent;
		$intent->media_file_id = $media_file_id;
		$intent->accessed_at = date('Y-m-d H:i:s');
		
		if ($ptm_source)
			$intent->source = $ptm_source;

		if ($ptm_context)
			$intent->context = $ptm_context;

		// set user agent
		$ua_string = trim($_SERVER['HTTP_USER_AGENT']);
		if (strlen($ua_string)) {
			if (!($agent = Model\UserAgent::find_one_by_user_agent($ua_string))) {
				$agent = new Model\UserAgent;
				$agent->user_agent = $ua_string;
				$agent->save();
			}
			$intent->user_agent_id = $agent->id;
		}

		// get ip, but don't store it
		$ip = IP\Address::factory($_SERVER['REMOTE_ADDR']);
		if (method_exists($ip, 'as_IPv6_address')) {
			$ip = $ip->as_IPv6_address();
		}
		$ip_string = $ip->format(IP\Address::FORMAT_COMPACT);

		// Generate a hash from IP address and UserAgent so we can identify
		// identical requests without storing an IP address.
		$intent->request_id = openssl_digest($ip_string . $ua_string, 'sha256');
		$intent = $intent->add_geo_data($ip_string);

		$intent->save();
	}

	$location = $media_file->add_ptm_parameters(
		$media_file->get_file_url(),
		array(
			'source'  => $intent->source,
			'context' => $intent->context
		)
	);

	header("HTTP/1.1 301 Moved Permanently");
	header("Location: " . $location);
	exit;
}
add_action( 'wp', '\Podlove\handle_media_file_download' );

/**
 * Extend/Replace WordPress core search logic to include episode fields.
 *
 * The way I do it here is not well-behaving. If other plugins modify the query
 * before me, their changes will be overridden. However, there is no better
 * place to hook into and I refuse to modify the filterable query string with
 * regular expressions.
 *
 * If you found this piece of code and are now cursing at me, please get in
 * touch. 
 */
add_filter('posts_search', function($search, $query) {
	global $wpdb;

	if (!isset($query->query_vars['search_terms']))
		return $search;

	$episodesTable = \Podlove\Model\Episode::table_name();

	$search = '';
	$searchand = '';
	$n = !empty($query->query_vars['exact']) ? '' : '%';
	foreach( (array) $query->query_vars['search_terms'] as $term ) {
		$term = esc_sql( like_escape( $term ) );
		$search .= "
			{$searchand}
			(
				($wpdb->posts.post_title LIKE '{$n}{$term}{$n}')
				OR
				($wpdb->posts.post_content LIKE '{$n}{$term}{$n}')
				OR
				($episodesTable.subtitle LIKE '{$n}{$term}{$n}')
				OR
				($episodesTable.summary LIKE '{$n}{$term}{$n}')
				OR
				($episodesTable.chapters LIKE '{$n}{$term}{$n}')
			)";
		$searchand = ' AND ';
	}

	if ( !empty($search) ) {
		$search = " AND ({$search}) ";
		if ( !is_user_logged_in() )
			$search .= " AND ($wpdb->posts.post_password = '') ";
	}

	return $search;
}, 10, 2);

// join into episode table in WordPress searches so we can access episode fields
add_filter('posts_join', function($join, $query) {
	global $wpdb;

	if (!$query->is_search())
		return $join;

	$episodesTable = \Podlove\Model\Episode::table_name();
	$join .= " LEFT JOIN $episodesTable ON $wpdb->posts.ID = $episodesTable.post_id ";

	return $join;
}, 10, 2);

// add route for file downloads
add_action( 'init', function () {
    add_rewrite_rule(
        '^podlove/file/([0-9]+)/s/([^/]+)/c/([^/]+)/.+/?$',
        'index.php?download_media_file=$matches[1]&ptm_source=$matches[2]&ptm_context=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        '^podlove/file/([0-9]+)/s/([^/]+)/.+/?$',
        'index.php?download_media_file=$matches[1]&ptm_source=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        '^podlove/file/([0-9]+)/.+/?$',
        'index.php?download_media_file=$matches[1]',
        'top'
    );
}, 10 );

add_filter( 'query_vars', function ( $query_vars ){
    $query_vars[] = 'download_media_file';
    $query_vars[] = 'ptm_source';
    $query_vars[] = 'ptm_context';
    return $query_vars;
}, 10, 1 );

// don't add trailing slash to file URLs
add_filter('redirect_canonical', function($redirect_url, $requested_url) {
	if ((int) get_query_var('download_media_file')) {
		return false;
	} else {
		return $redirect_url;
	}
}, 10, 2);

// Ensure WordPress importer keeps the mapping id for old<->new post id.
// This is required for the Im/Export module. To avoid user errors, it is
// better to keep this behaviour in core.
add_filter( 'wp_import_post_meta', function($postmetas, $post_id, $post) {
	$postmetas[] = array(
		'key' => 'import_id',
		'value' => $post_id
	);
	return $postmetas;
}, 10, 3 );

// register ajax actions
new \Podlove\AJAX\Ajax;

// add podlove to admin bar
add_action( 'admin_bar_menu', function ( $wp_admin_bar ) {
	$wp_admin_bar->add_node( array(
		'id'     => 'podlove-settings',
		'parent' => 'site-name',
		'title'  => 'Podlove',
		'href'   => admin_url( 'admin.php?page=podlove_settings_handle' )
	) );
}, 50 );

add_action( 'admin_print_styles', function () {
	wp_register_style( 'podlove-admin', \Podlove\PLUGIN_URL . '/css/admin.css', array(), \Podlove\get_plugin_header( 'Version' ) );
	wp_enqueue_style( 'podlove-admin' );

	wp_register_style( 'podlove-admin-chosen', \Podlove\PLUGIN_URL . '/js/admin/chosen/chosen.min.css', array(), \Podlove\get_plugin_header( 'Version' ) );
	wp_enqueue_style( 'podlove-admin-chosen' );

	wp_register_style( 'podlove-admin-image-chosen', \Podlove\PLUGIN_URL . '/js/admin/chosen/chosenImage.css', array(), \Podlove\get_plugin_header( 'Version' ) );
	wp_enqueue_style( 'podlove-admin-image-chosen' );

	wp_register_style( 'podlove-admin-font', \Podlove\PLUGIN_URL . '/css/admin-font.css', array(), \Podlove\get_plugin_header( 'Version' ) );
	wp_enqueue_style( 'podlove-admin-font' );

	wp_register_script( 'podlove-cryptojs', \Podlove\PLUGIN_URL . '/js/admin/cryptojs/md5.js' );
	wp_enqueue_script( 'podlove-cryptojs' );
} );
