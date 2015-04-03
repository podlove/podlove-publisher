<?php
/**
 * Plugin Name: Podlove Podcast Publisher
 * Plugin URI:  http://podlove.org/podlove-podcast-publisher/
 * Description: The one and only next generation podcast publishing system. Seriously. It's magical and sparkles a lot.
 * Version:     2.1.0
 * Author:      Podlove
 * Author URI:  http://podlove.org
 * License:     MIT
 * License URI: license.txt
 * Text Domain: podlove
 */

$correct_php_version = version_compare( phpversion(), "5.4", ">=" );

if ( ! $correct_php_version ) {
	echo "Podlove Podcasting Plugin requires <strong>PHP 5.4</strong> or higher.<br>";
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
