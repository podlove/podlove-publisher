<?php

$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = '/wordpress-phpunit';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find WordPress tests directory at: {$_tests_dir}\n";
    exit(1);
}

if (file_exists(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
    require dirname(__DIR__, 2) . '/vendor/autoload.php';
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', function () {
    require dirname(__DIR__, 2) . '/podlove.php';
});

require_once $_tests_dir . '/includes/bootstrap.php';
