<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
    'finders' => [
        Finder::create()->files()->in('vendor/piwik/*')->name(['*.php', '*.yml', 'LICENSE', 'composer.json']),
        Finder::create()->files()->in('vendor/mustangostang/*')->name(['*.php', 'LICENSE', 'composer.json']),
    ],
    'patchers' => [
        function (string $filePath, string $prefix, string $content): string {
            $content = str_replace(
                'class_exists(\'DeviceDetector',
                'class_exists(\''.$prefix.'\\\\DeviceDetector',
                $content
            );

            $content = str_replace(
                '$className = \'DeviceDetector',
                '$className = \''.$prefix.'\\\\DeviceDetector',
                $content
            );

            return $content.'';
        }
    ]
];
