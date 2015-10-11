<?php
/**
 * Plugin Name: Podlove Podcast Publisher
 * Plugin URI:  http://publisher.podlove.org
 * Description: The one and only next generation podcast publishing system. Seriously. It's magical and sparkles a lot.
 * Version:     2.3.1
 * Author:      Podlove
 * Author URI:  http://podlove.org
 * License:     MIT
 * License URI: license.txt
 * Text Domain: podlove
 */

function load_podlove_podcast_publisher() {
	require_once 'vendor/autoload.php'; # composer autoloader
	require_once 'bootstrap/bootstrap.php';
	require_once 'lib/helper.php';
	require_once 'lib/cron.php';
	require_once 'lib/network.php';
	require_once 'lib/php/array.php';
	require_once 'lib/php/string.php';
	require_once 'lib/version.php';
	require_once 'lib/feeds.php';
	require_once 'lib/shortcodes.php';
	require_once 'plugin.php';
}

function deactivate_podlove_podcast_publisher() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}

function podlove_admin_error_no_autoload() {
	?>
	<div id="message" class="error">
		<p>
			<strong>Podlove Podcast Publisher could not be activated</strong>
		</p>
		<p>
			Plugin files are incomplete. Please download a fresh copy of the plugin: <a href="https://downloads.wordpress.org/plugin/podlove-podcasting-plugin-for-wordpress.zip">downloads.wordpress.org/plugin/podlove-podcasting-plugin-for-wordpress.zip</a> and <a href="https://codex.wordpress.org/Managing_Plugins#Installing_Plugins">repeat the installation</a>.
		</p>
	</div>
	<?php
	deactivate_podlove_podcast_publisher();
}

$correct_php_version = version_compare( phpversion(), "5.4", ">=" );

if ( ! $correct_php_version ) {
	echo "Podlove Podcasting Plugin requires <strong>PHP 5.4</strong> or higher.<br>";
	echo "You are running PHP " . phpversion();
	deactivate_podlove_podcast_publisher();
	// It's important to _exit_ here instead of showing an error message for
	// installation workflow. When the PHP version is too low, WordPress 
	// automatically disables the plugin and displays the message.
	// For _upgrading_ users there is the "white screen of death", but only
	// once, because we also make sure to deactivate the plugin if this occurs.
	exit;
} else if (!file_exists(trailingslashit(dirname(__FILE__)) . 'vendor/autoload.php')) {
	// Looks like this can happen on cheap shared hosting. Update fails and leaves
	// the Publisher in an unusable state. From experience it's always at least
	// 'vendor/autoload.php' that is missing. This also catches users that accidentally
	// download the development version from GitHub.
	add_action('admin_notices', 'podlove_admin_error_no_autoload');
} else {
	load_podlove_podcast_publisher();
}
