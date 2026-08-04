#!/usr/bin/env php
<?php

$classmap_path = __DIR__.'/../dist/vendor/composer/autoload_classmap.php';

if (!is_file($classmap_path)) {
    fwrite(STDERR, "Composer classmap not found: {$classmap_path}\n");
    exit(1);
}

$classmap = require $classmap_path;
$missing_files = [];

foreach ($classmap as $class_name => $class_file) {
    if (!is_file($class_file)) {
        $missing_files[$class_name] = $class_file;
    }
}

if ($missing_files) {
    foreach ($missing_files as $class_name => $class_file) {
        fwrite(STDERR, "Missing autoload file for {$class_name}: {$class_file}\n");
    }

    exit(1);
}

printf("Verified %d Composer classmap entries.\n", count($classmap));
