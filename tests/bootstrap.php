<?php

$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = '';
$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../podlove.php';
	echo "Installing Podlove Publisher...\n";
	\Podlove\activate_for_current_blog();
}
tests_add_filter('plugins_loaded', '_manually_load_plugin');

require $_tests_dir . '/includes/bootstrap.php';

require_once 'helper/episode_factory.php';