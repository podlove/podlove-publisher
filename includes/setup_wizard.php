<?php
// status: skeleton page, needs enabling via constant

namespace Podlove\Wizard;

define('PODLOVE_WIZARD_URL_KEY', 'podlove-setup-wizard');

add_action('wp_loaded', '\Podlove\Wizard\wizard_page', 9);
add_action('admin_init', '\Podlove\Wizard\maybe_redirect_to_wizard_page', 10);

function maybe_redirect_to_wizard_page()
{
    // enable with:
    // define('PODLOVE_WIZARD_ENABLED', true);
    if (!(defined('PODLOVE_WIZARD_ENABLED') && PODLOVE_WIZARD_ENABLED)) {
        return;
    }

    // allow to disable wizard via filter
    if (!apply_filters('podlove_enable_setup_wizard', true)) {
        return;
    }

    // don't redirect when we're on the page
    if ($_GET['page'] == PODLOVE_WIZARD_URL_KEY) {
        return;
    }

    // don't redirect when it does not make technically sense
    if (wp_doing_ajax() || is_network_admin()) {
        return;
    }

    // missing checks:
    // - user has enough permissions
    // - wizard is done

    wp_safe_redirect(admin_url('admin.php?page='.PODLOVE_WIZARD_URL_KEY));

    exit;
}

function wizard_page()
{
    if (!isset($_GET['page'])) {
        return;
    }

    if ($_GET['page'] != PODLOVE_WIZARD_URL_KEY) {
        return;
    }

    wp_enqueue_script('podlove-episode-vue-apps', \Podlove\PLUGIN_URL.'/js/dist/app.js', ['underscore', 'jquery']); ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <title>Podlove Publisher | Setup Wizard</title>
</head>
<body>
    <div id="podlove-setup-wizard"></div>
    <?php wp_footer(); ?>
</body>
</html>
    <?php
    exit;
}
