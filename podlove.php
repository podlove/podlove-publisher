<?php
/**
 * Plugin Name: Podlove Podcast Publisher
 * Plugin URI:  http://wordpress.org/extend/plugins/podlove-podcasting-plugin-for-wordpress/
 * Description: The one and only next generation podcast feed generator. Seriously. It's magical and sparkles a lot.
 * Version:     1.10.11-alpha
 * Author:      Podlove
 * Author URI:  http://podlove.org
 * License:     MIT
 * License URI: license.txt
 * Text Domain: podlove
 */

$correct_php_version = version_compare( phpversion(), "5.3", ">=" );

if ( ! $correct_php_version ) {
	echo "Podlove Podcasting Plugin requires <strong>PHP 5.3</strong> or higher.<br>";
	echo "You are running PHP " . phpversion();
	exit;
}

require_once 'vendor/autoload.php'; # composer autoloader
require_once 'bootstrap/bootstrap.php';
require_once 'lib/helper.php';
require_once 'lib/version.php';
require_once 'lib/feeds.php';
require_once 'lib/shortcodes.php';
require_once 'lib/no_enclosure_autodiscovery.php';
require_once 'plugin.php';
