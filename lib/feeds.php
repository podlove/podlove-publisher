<?php

namespace Podlove\Feeds;

use Podlove\Model;

// Prio 11 so it hooks *after* the domain mapping plugin.
// This is important when one moves a domain. That way the domain gets
// remapped/redirected correctly by the domain mapper before being redirected by us.
add_action('template_redirect', '\Podlove\Feeds\handle_feed_proxy_redirects', 11);

add_action('init', '\Podlove\Feeds\register_podcast_feeds');

function register_podcast_feeds()
{
    foreach (Model\Feed::all() as $feed) {
        if ($feed->slug) {
            add_feed($feed->slug, '\\Podlove\\Feeds\\generate_podcast_feed');
        }
    }

    // changing feed settings may affect permalinks, so we need to flush
    if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'podlove_feeds_settings_handle') {
        set_transient('podlove_needs_to_flush_rewrite_rules', true);
    }
}

/**
 * Handles feed requests.
 *
 * - ensures correct feed URL protocol
 * - ensures canonical feed URL
 * - redirects to feed proxy if necessary
 * - prepares podcast feed (adds all metadata etc. to RSS)
 */
function handle_feed_proxy_redirects()
{
    if (!$feed = get_feed()) {
        return;
    }

    header('Content-Type: application/rss+xml; charset='.get_option('blog_charset'), true);

    maybe_redirect_to_canonical_url();

    $redirect_url = $feed->get_redirect_url();

    if ($redirect_url && $feed->is_redirect_enabled() && !is_page_in_feed() && should_redirect_to_proxy()) {
        header(sprintf('Location: %s', $redirect_url), true, $feed->get_redirect_http_status_code());
        exit;
    }   // don't redirect; prepare feed
    status_header(200);
    RSS::prepare_feed($feed->slug);
}

/**
 * Is the current request part of a "paged feed"?
 *
 * @return bool
 */
function is_page_in_feed()
{
    return get_query_var('paged', 1) > 1;
}

/**
 * Get canonical subscribe feed URL.
 *
 * If current request is a "paged feed" page, this parameter is preserved.
 *
 * @return string
 */
function get_canonical_feed_url()
{
    if (!$feed = get_feed()) {
        return null;
    }

    $url = $feed->get_subscribe_url();

    if (is_page_in_feed()) {
        $url = add_query_arg(['paged' => get_query_var('paged', 1)], $url);
    }

    return $url;
}

/**
 * Should the current feed request be delivered in debug mode?
 *
 * @return bool
 */
function is_debug_view()
{
    return \Podlove\get_setting('website', 'feeds_skip_redirect') == 'on' && filter_input(INPUT_GET, 'redirect') == 'no';
}

/**
 * Should the current feed request be allowed to redirect to a feed proxy?
 *
 * @return bool
 */
function should_redirect_to_proxy()
{
    // don't redirect when debug view is active
    if (is_debug_view()) {
        return false;
    }

    // don't redirect when a feed proxy crawler is requesting
    if (preg_match('/feedburner|feedsqueezer|feedvalidator|feedpress/i', $_SERVER['HTTP_USER_AGENT'])) {
        return false;
    }

    return true;
}

/**
 * Get Feed object from current context.
 *
 * @return Podlove\Model\Feed
 */
function get_feed()
{
    if (!is_feed()) {
        return null;
    }

    if (!$feed_slug = get_query_var('feed')) {
        return null;
    }

    return Model\Feed::find_one_by_slug($feed_slug);
}

/**
 * Maybe redirect to canonical feed URL.
 *
 * It's important that there is only one "correct" subscribe URL.
 * When accessing a feed, ensure the canonical form is used and redirect if necessary.
 */
function maybe_redirect_to_canonical_url()
{
    // do not redirect if feed debug mode is active
    if (is_debug_view()) {
        return;
    }

    // do not redirect if pretty permalinks are turned off
    if (strlen(trim(get_option('permalink_structure'))) === 0) {
        return;
    }

    if (!$feed = get_feed()) {
        return;
    }

    $feed_url = $feed->get_subscribe_url();
    $request_url = 'http'.(is_ssl() ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    $url = parse_url($request_url);

    if (
        !\Podlove\PHP\ends_with($url['path'], '/') && \Podlove\PHP\ends_with($feed_url, '/')
        ||
        \Podlove\PHP\ends_with($url['path'], '/') && !\Podlove\PHP\ends_with($feed_url, '/')
    ) {
        wp_redirect(get_canonical_feed_url(), 301);
        exit;
    }
}

function generate_podcast_feed()
{
    remove_podPress_hooks();
    remove_powerPress_hooks();
    RSS::render();
}

function check_for_and_do_compression($content_type = 'application/rss+xml')
{
    // ensure content type headers are set
    if (!headers_sent()) {
        header('Content-type: '.$content_type);
    }

    if (!apply_filters('podlove_enable_gzip_for_feeds', true)) {
        return false;
    }

    // gzip requires zlib extension
    if (!extension_loaded('zlib')) {
        return false;
    }

    // if zlib output compression is already active, don't gzip
    // (both cannot be active at the same time)
    $ob_status = ob_get_status();
    if (isset($ob_status['name']) && $ob_status['name'] == 'zlib output compression') {
        return false;
    }

    // don't gzip if client doesn't accept it
    if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) {
        return false;
    }

    // don't gzip if _any_ output buffering is active
    // this can be 1 if "output_buffering" is not set to off / 0
    // but better safe than sorry (otherwise there's trouble with caching plugins)
    if (ob_get_level() > 0) {
        return false;
    }

    // don't gzip if wprocket is active
    if (in_array('do_rocket_callback', ob_list_handlers())) {
        return false;
    }

    // don't gzip if gzipping is already active
    if (in_array('ob_gzhandler', ob_list_handlers())) {
        return false;
    }

    // don't try to use ob_gzhandler on hhvm, it's not supported
    // (see https://github.com/facebook/hhvm/issues/1854)
    if (defined('HHVM_VERSION')) {
        return false;
    }

    // start gzipping
    ob_start('ob_gzhandler');
}

/**
 * Make sure that PodPress doesn't vomit anything into our precious feeds
 * in case it is still active.
 */
function remove_podPress_hooks()
{
    remove_filter('option_blogname', 'podPress_feedblogname');
    remove_filter('option_blogdescription', 'podPress_feedblogdescription');
    remove_filter('option_rss_language', 'podPress_feedblogrsslanguage');
    remove_filter('option_rss_image', 'podPress_feedblogrssimage');
    remove_action('rss2_ns', 'podPress_rss2_ns');
    remove_action('rss2_head', 'podPress_rss2_head');
    remove_filter('rss_enclosure', 'podPress_dont_print_nonpodpress_enclosures');
    remove_action('rss2_item', 'podPress_rss2_item');
    remove_action('atom_head', 'podPress_atom_head');
    remove_filter('atom_enclosure', 'podPress_dont_print_nonpodpress_enclosures');
    remove_action('atom_entry', 'podPress_atom_entry');
}

function remove_powerPress_hooks()
{
    remove_action('rss2_ns', 'powerpress_rss2_ns');
    remove_action('rss2_head', 'powerpress_rss2_head');
    remove_action('rss2_item', 'powerpress_rss2_item');
}
