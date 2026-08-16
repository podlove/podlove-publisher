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

$autoload_path = __DIR__.'/../dist/vendor/autoload.php';

if (!is_file($autoload_path)) {
    fwrite(STDERR, "Composer autoloader not found: {$autoload_path}\n");
    exit(1);
}

require $autoload_path;

$loader = new PodlovePublisher_Vendor\Twig\Loader\ArrayLoader([
    'macro' => '{% macro hello() %}hello{% endmacro %}',
    'page' => '{% import "macro" as macro %}{{ macro.hello() }}',
]);
$twig = new PodlovePublisher_Vendor\Twig\Environment($loader, ['autoescape' => false]);

try {
    $result = $twig->render('page');
} catch (Throwable $error) {
    fwrite(STDERR, "Twig distribution smoke test failed: {$error->getMessage()}\n");
    exit(1);
}

if ('hello' !== $result) {
    fwrite(STDERR, "Twig distribution smoke test returned unexpected output: {$result}\n");
    exit(1);
}

fwrite(STDOUT, "Verified Twig macro rendering.\n");
