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
	Model\DownloadIntent::build();
	Model\DownloadIntentClean::build();
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
	$default_modules = array( 'podlove_web_player', 'open_graph', 'asset_validation', 'logging', 'oembed', 'feed_validation', 'import_export', 'subscribe_button' );
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
{% if not is_feed() %}

	{# display web player for episode #}
	{{ episode.player }}
	
	{# display download menu for episode #}
	{% include '@core/shortcode/downloads-select.twig' %}

{% endif %}
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
	podlove_run_system_report();
}

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
	Model\DownloadIntentClean::destroy();
	Model\UserAgent::destroy();
	Model\GeoArea::destroy();
	Model\GeoAreaName::destroy();
}

/**
 * Activate internal modules.
 */
add_action( 'init', array( '\Podlove\Custom_Guid', 'init' ) );
add_action( 'init', array( '\Podlove\Downloads', 'init' ) );
add_action( 'init', array( '\Podlove\FeedDiscoverability', 'init' ) );
add_action( 'init', array( '\Podlove\Geo_Ip', 'init' ) );
add_action( 'init', array( '\Podlove\DuplicatePost', 'init' ) );
add_action( 'init', array( '\Podlove\Analytics\EpisodeDownloadAverage', 'init' ) );
add_action( 'init', array( '\Podlove\Analytics\DownloadIntentCleanup', 'init' ) );

add_action( 'admin_init', array( '\Podlove\Repair', 'init' ) );
add_action( 'admin_init', array( '\Podlove\DeleteHeadRequests', 'init' ) );
add_action( 'admin_init', array( '\Podlove\PhpDeprecationWarning', 'init' ) );

// init cache (after plugins_loaded, so modules have a chance to hook)
add_action( 'init', array( '\Podlove\Cache\TemplateCache', 'get_instance' ) );

require_once \Podlove\PLUGIN_DIR . 'includes/admin_styles.php';
require_once \Podlove\PLUGIN_DIR . 'includes/cache.php';
require_once \Podlove\PLUGIN_DIR . 'includes/chapters.php';
require_once \Podlove\PLUGIN_DIR . 'includes/cover_art.php';
require_once \Podlove\PLUGIN_DIR . 'includes/downloads.php';
require_once \Podlove\PLUGIN_DIR . 'includes/explicit_content.php';
require_once \Podlove\PLUGIN_DIR . 'includes/extras.php';
require_once \Podlove\PLUGIN_DIR . 'includes/feed_discovery.php';
require_once \Podlove\PLUGIN_DIR . 'includes/frontend_styles.php';
require_once \Podlove\PLUGIN_DIR . 'includes/import.php';
require_once \Podlove\PLUGIN_DIR . 'includes/license.php';
require_once \Podlove\PLUGIN_DIR . 'includes/merge_episodes.php';
require_once \Podlove\PLUGIN_DIR . 'includes/modules.php';
require_once \Podlove\PLUGIN_DIR . 'includes/permalinks.php';
require_once \Podlove\PLUGIN_DIR . 'includes/recording_date.php';
require_once \Podlove\PLUGIN_DIR . 'includes/redirects.php';
require_once \Podlove\PLUGIN_DIR . 'includes/search.php';
require_once \Podlove\PLUGIN_DIR . 'includes/system_report.php';
require_once \Podlove\PLUGIN_DIR . 'includes/templates.php';
require_once \Podlove\PLUGIN_DIR . 'includes/trash.php';

// @todo: change to internal module
new \Podlove\AJAX\Ajax(); 
