<?php
/**
 * Plugin Name: Podlove Podcasting Plugin for WordPress
 * Plugin URI:  http://wordpress.org/extend/plugins/podlove-podcasting-plugin-for-wordpress/
 * Description: The one and only next generation podcast feed generator. Seriously. It's magical and sparkles a lot.
 * Version:     1.2.14-alpha
 * Author:      eteubert
 * Author URI:  ericteubert@googlemail.com
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

require_once 'bootstrap/bootstrap.php';
require_once 'lib/helper.php';
require_once 'lib/version.php';
require_once 'lib/feeds.php';
require_once 'lib/shortcodes.php';
require_once 'lib/no_enclosure_autodiscovery.php';
require_once 'plugin.php';