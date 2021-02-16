<?php

add_action('admin_notices', 'podlove_donation_banner');
add_action('wp_ajax_podlove-hide-donation-banner', 'podlove_donation_banner_hide');

define('PODLOVE_DONATION_BANNER_OPTION_KEY', '_podlove_hide_donation_banner');
define('PODLOVE_DONATION_BANNER_MIN_EPISODES', 5);

function podlove_donation_banner()
{
    // don't show if user has hidden the banner
    if (get_option(PODLOVE_DONATION_BANNER_OPTION_KEY)) {
        return;
    }

    // only show on podlove settings pages
    $page_key = filter_input(INPUT_GET, 'page');
    if (strpos($page_key, 'podlove') === false) {
        return;
    }

    // only show when some episodes have been published
    if (podlove_donation_count_published_episodes() < PODLOVE_DONATION_BANNER_MIN_EPISODES) {
        return;
    }

    include 'donation_banner.html.php';
}

function podlove_donation_banner_hide()
{
    update_option(PODLOVE_DONATION_BANNER_OPTION_KEY, true);
}

function podlove_donation_count_published_episodes()
{
    global $wpdb;

    $sql = 'SELECT COUNT(*) FROM `'.$wpdb->posts.'` p WHERE p.`post_status` IN (\'publish\', \'private\') AND p.post_type = "podcast"';

    return $wpdb->get_var($sql);
}
