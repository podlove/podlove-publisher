<?php
/*
Plugin Name: Podlove Podcasting Plugin for WordPress
Plugin URI: https://github.com/eteubert/podlove
Description: The one and only podcast feed generator. Seriously.
Version: 0.9.0-alpha
Author: eteubert
Author URI: ericteubert@googlemail.com
License: MIT
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
require_once 'plugin.php';