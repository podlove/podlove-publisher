<?php

namespace Podlove;

register_activation_hook(PLUGIN_FILE, __NAMESPACE__.'\activate');
register_deactivation_hook(PLUGIN_FILE, __NAMESPACE__.'\deactivate');
register_uninstall_hook(PLUGIN_FILE, __NAMESPACE__.'\uninstall');
add_action('wpmu_new_blog', '\Podlove\create_new_blog', 10, 6);
add_action('delete_blog', '\Podlove\delete_blog', 10, 2);

function activate_for_current_blog()
{
    \podlove_setup_database_tables();
    \podlove_init_capabilities();
    \podlove_setup_file_types();
    \podlove_setup_podcast();
    \podlove_setup_modules();
    \podlove_setup_expert_settings();
    \podlove_setup_default_template();
    \podlove_setup_default_media();
    \podlove_setup_default_asset_assignments();
}

/**
 * Hook: Create a new blog in a multisite environment.
 *
 * When a new blog is created, we have to trigger the activation function
 * for in the scope of that blog.
 *
 * @param mixed $blog_id
 * @param mixed $user_id
 * @param mixed $domain
 * @param mixed $path
 * @param mixed $site_id
 * @param mixed $meta
 */
function create_new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta)
{
    switch_to_blog($blog_id);

    $plugin_base_path = implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, PLUGIN_FILE), -2));

    if (is_plugin_active_for_network($plugin_base_path)) {
        activate_for_current_blog();
    }

    restore_current_blog();
}

/**
 * Fires before a blog is deleted.
 *
 * @param int  $blog_id the blog ID
 * @param bool $drop    true if blog's table should be dropped
 */
function delete_blog($blog_id, $drop)
{
    if ($drop) {
        uninstall_for_current_blog();
    }
}

/**
 * Hook: Activate the plugin.
 *
 * In a single blog install, just call activate_for_current_blog().
 * However, in a multisite install, iterate over all blogs and call the activate
 * function for each of them.
 *
 * @param mixed $network_wide
 */
function activate($network_wide)
{
    global $wpdb;

    if ($network_wide) {
        set_time_limit(0); // may take a while, depending on network size
        $blogids = $wpdb->get_col('SELECT blog_id FROM '.$wpdb->blogs);
        foreach ($blogids as $blog_id) {
            \Podlove\with_blog_scope($blog_id, function () {
                activate_for_current_blog();
            });
        }
    } else {
        activate_for_current_blog();
    }

    set_transient('podlove_needs_to_flush_rewrite_rules', true);
    podlove_run_system_report();
}

function deactivate()
{
    flush_rewrite_rules();
}

/**
 * Hook: Uninstall the plugin.
 *
 * In a single blog install, just call uninstall_for_current_blog().
 * However, in a multisite install, iterate over all blogs and call the
 * uninstall function for each of them.
 */
function uninstall()
{
    global $wpdb;

    if (is_multisite()) {
        if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
            $current_blog = $wpdb->blogid;
            $blogids = $wpdb->get_col('SELECT blog_id FROM '.$wpdb->blogs);
            foreach ($blogids as $blog_id) {
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

function uninstall_for_current_blog()
{
    global $wpdb;

    \Podlove\Cache\TemplateCache::get_instance()->purge();

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

    do_action('podlove_uninstall_plugin');

    // trash all episodes
    $query = new \WP_Query(['post_type' => 'podcast']);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            wp_trash_post(get_the_ID());
        }
    }

    wp_reset_postdata();

    // clean up options
    $options = [
        'podlove_feed%',
        '%podlove_chapters_string_%',
        'podlove_podcast',
        'podlove_active_modules',
        'podlove_template_assignment',
        'podlove_webplayer_formats',
        'podlove_asset_assignment',
        'podlove_global_messages',
        'podlove_database_version',
        'podlove_repair_log',
        'podlove_import_file',
        'podlove_import_tracking_file',
        '_podlove_added_bitlove_to_feed_model',
        'podlove_contributors',
        'podlove_flattr',
        'podlove_geo_tracking',
        'podlove_metadata',
        'podlove_module%',
        'podlove_redirects',
        'podlove_title_migration_state',
        'podlove_tracking',
        'podlove_website',
        'podlove_analytics_tiles',
    ];

    foreach ($options as $option) {
        $wpdb->query('DELETE FROM '.$wpdb->options.' WHERE option_name LIKE "'.$option.'"');
    }
}

// prepare for translation
if (did_action('plugins_loaded')) {
    \Podlove\plugin_textdomain();
} else {
    add_action('plugins_loaded', '\Podlove\plugin_textdomain', 99);
}

function plugin_textdomain()
{
    // only use language packs, see http://ottopress.com/2013/language-packs-101-prepwork/
    load_plugin_textdomain('podlove-podcasting-plugin-for-wordpress');
}

// Activate internal modules.
add_action('init', ['\Podlove\Custom_Guid', 'init']);
add_action('init', ['\Podlove\Downloads', 'init']);
add_action('init', ['\Podlove\Geo_Ip', 'init']);
add_action('init', ['\Podlove\DuplicatePost', 'init']);
add_action('init', ['\Podlove\Analytics\EpisodeDownloadAverage', 'init']);
add_action('init', ['\Podlove\Analytics\DownloadIntentCleanup', 'init']);
add_action('init', ['\Podlove\Analytics\DownloadSumsCalculator', 'init']);
add_action('init', ['\Podlove\Analytics\SaltShaker', 'init']);

add_action('admin_init', ['\Podlove\Repair', 'init']);
add_action('admin_init', ['\Podlove\PhpDeprecationWarning', 'init']);

// init cache (after plugins_loaded, so modules have a chance to hook)
add_action('init', ['\Podlove\Cache\TemplateCache', 'get_instance']);

// require_once \Podlove\PLUGIN_DIR . 'includes/about.php';
require_once \Podlove\PLUGIN_DIR.'includes/api/api.php';
require_once \Podlove\PLUGIN_DIR.'includes/auto_post_titles.php';
require_once \Podlove\PLUGIN_DIR.'includes/cache.php';
require_once \Podlove\PLUGIN_DIR.'includes/capabilities.php';
require_once \Podlove\PLUGIN_DIR.'includes/chapters.php';
require_once \Podlove\PLUGIN_DIR.'includes/cover_art.php';
require_once \Podlove\PLUGIN_DIR.'includes/deprecations.php';
require_once \Podlove\PLUGIN_DIR.'includes/detect_duplicate_slugs.php';
require_once \Podlove\PLUGIN_DIR.'includes/donation_banner.php';
require_once \Podlove\PLUGIN_DIR.'includes/downloads.php';
require_once \Podlove\PLUGIN_DIR.'includes/episode_number_column.php';
require_once \Podlove\PLUGIN_DIR.'includes/episode_number_quick_edit_form.php';
require_once \Podlove\PLUGIN_DIR.'includes/explicit_content.php';
require_once \Podlove\PLUGIN_DIR.'includes/extras.php';
require_once \Podlove\PLUGIN_DIR.'includes/feed_discovery.php';
require_once \Podlove\PLUGIN_DIR.'includes/frontend_styles.php';
require_once \Podlove\PLUGIN_DIR.'includes/http.php';
require_once \Podlove\PLUGIN_DIR.'includes/images.php';
require_once \Podlove\PLUGIN_DIR.'includes/import.php';
require_once \Podlove\PLUGIN_DIR.'includes/jetpack.php';
require_once \Podlove\PLUGIN_DIR.'includes/license.php';
require_once \Podlove\PLUGIN_DIR.'includes/merge_episodes.php';
require_once \Podlove\PLUGIN_DIR.'includes/modules.php';
require_once \Podlove\PLUGIN_DIR.'includes/no_enclosure_autodiscovery.php';
require_once \Podlove\PLUGIN_DIR.'includes/permalinks.php';
require_once \Podlove\PLUGIN_DIR.'includes/podlove-web-player-5.php';
require_once \Podlove\PLUGIN_DIR.'includes/recording_date.php';
require_once \Podlove\PLUGIN_DIR.'includes/redirects.php';
require_once \Podlove\PLUGIN_DIR.'includes/request_id_rehash.php';
require_once \Podlove\PLUGIN_DIR.'includes/require_curl.php';
require_once \Podlove\PLUGIN_DIR.'includes/setup.php';
require_once \Podlove\PLUGIN_DIR.'includes/setup_wizard.php';
require_once \Podlove\PLUGIN_DIR.'includes/screen_options.php';
require_once \Podlove\PLUGIN_DIR.'includes/scripts_and_styles.php';
require_once \Podlove\PLUGIN_DIR.'includes/search.php';
require_once \Podlove\PLUGIN_DIR.'includes/system_report.php';
require_once \Podlove\PLUGIN_DIR.'includes/templates.php';
require_once \Podlove\PLUGIN_DIR.'includes/template_pages.php';
require_once \Podlove\PLUGIN_DIR.'includes/theme_helper.php';
require_once \Podlove\PLUGIN_DIR.'includes/trash.php';
require_once \Podlove\PLUGIN_DIR.'includes/verify_itunes_category.php';
require_once \Podlove\PLUGIN_DIR.'includes/wp_rocket.php';

require_once \Podlove\PLUGIN_DIR.'lib/tools.php';

// @todo: change to internal module
new \Podlove\AJAX\Ajax();
new \Podlove\Settings\Tools\UserAgentRefresh();

\Podlove\Jobs\CronJobRunner::init();
\Podlove\Jobs\ToolsSection::init();
\Podlove\Jobs\JobCleaner::init();
