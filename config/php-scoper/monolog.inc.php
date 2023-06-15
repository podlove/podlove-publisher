<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
    'finders' => [
        Finder::create()->files()->in('vendor/monolog/*')->name(['*.php', 'LICENSE', 'composer.json']),
    ],
    'patchers' => [
    ]
];
