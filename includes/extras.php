<?php

/**
 * Tiny behavior additions.
 *
 * Code should be moved into a separate file if:
 * - more than one hook is involved
 * - logic exceeds 10is lines
 */

/*
 * Hackish workaround to flush rewrite rules.
 *
 * flush_rewrite_rules() is expensive, so it should only be called once.
 * However, calling it on activaton doesn't work. So I add a temporary flag
 * and call it when the flag exists. Transient is also used in other places
 * where rules must be rewritten.
 */
add_action('admin_init', function () {
    if (delete_transient('podlove_needs_to_flush_rewrite_rules')) {
        flush_rewrite_rules();
    }
}, 100);

// initialize post type
add_action('init', function () {
    new \Podlove\Podcast_Post_Type();
    \Podlove\SlugFreeze::init();
});

// apply domain mapping plugin where it's essential
add_action('plugins_loaded', function () {
    if (function_exists('domain_mapping_post_content')) {
        add_filter('feed_link', 'domain_mapping_post_content', 20);
        add_filter('podlove_subscribe_url', 'domain_mapping_post_content', 20);
    }
});

/*
 * When changing from an external cover asset to 'manual', copy external url
 * into local field.
 */
add_filter('pre_update_option_podlove_asset_assignment', function ($new, $old) {
    if (!isset($old['image']) || !isset($new['image'])) {
        return $new;
    }

    if ($new['image'] != 'manual') {  // just changes to manual
        return $new;
    }

    if (((int) $old['image']) <= 0) { // just changes from an asset
        return $new;
    }

    \Podlove\Log::get()->addInfo('Copying cover art from asset to manual');

    $episodes = \Podlove\Model\Episode::find_all_by_time();

    foreach ($episodes as $episode) {
        if ($cover_art = $episode->cover_art()) {
            $url = $cover_art->source_url();
            \Podlove\Log::get()->addInfo('Copying cover art '.$url.' from asset to manual for episode '.$episode->id);
            $episode->update_attribute('cover_art', $url);
        }
    }

    return $new;
}, 10, 2);
