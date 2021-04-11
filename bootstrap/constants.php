<?php

namespace Podlove;

/*
 * Conventions
 *
 * 	Plugin Name:		This Is My Plugin
 * 	Plugin Namespace:	ThisIsMyPlugin
 * 	Plugin File:		this-is-my-plugin.php
 * 	Plugin Textdomain:	this-is-my-plugin
 * 	Plugin Directory:	this-is-my-plugin
 */

define(__NAMESPACE__.'\PLUGIN_FILE_NAME', strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', __NAMESPACE__)).'.php');
define(__NAMESPACE__.'\PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)));
define(__NAMESPACE__.'\PLUGIN_FILE', PLUGIN_DIR.PLUGIN_FILE_NAME);
define(__NAMESPACE__.'\PLUGIN_URL', plugins_url('', PLUGIN_FILE));

/**
 * Get a value of the plugin header.
 *
 * @param mixed $tag_name
 */
function get_plugin_header($tag_name)
{
    static $plugin_data; // only load file once

    if (!function_exists('get_plugin_data')) {
        require_once ABSPATH.'/wp-admin/includes/plugin.php';
    }

    $plugin_data = get_plugin_data(PLUGIN_FILE, false, false);

    return $plugin_data[$tag_name];
}

define(__NAMESPACE__.'\PLUGIN_NAME', get_plugin_header('Name'));
define(__NAMESPACE__.'\TEXTDOMAIN', strtolower(str_replace(' ', '-', PLUGIN_NAME)));
load_plugin_textdomain(TEXTDOMAIN, false, TEXTDOMAIN.'/languages');
