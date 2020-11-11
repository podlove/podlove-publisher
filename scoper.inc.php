<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
    // By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
    // directory. You can however define which files should be scoped by defining a collection of Finders in the
    // following configuration key.
    //
    // For more see: https://github.com/humbug/php-scoper#finders-and-paths
    'finders' => [
        Finder::create()->files()->in('vendor/twig/*')->name(['*.php', 'LICENSE', 'composer.json']),
    ],

    'patchers' => [
        function (string $filePath, string $prefix, string $content): string {
            // suppress warnings for class_alias
            $content = preg_replace('/(\\\\class_alias)/', '@${1}', $content);

            if (stristr($filePath, 'ModuleNode.php')) {
                $content = str_replace(
                    'use Twig\\',
                    'use '.$prefix.'\\\\Twig\\',
                    $content
                );
            }

            if (stristr($filePath, 'GetAttrExpression.php')) {
                $content = str_replace(
                    'twig_get_attribute',
                    $prefix.'\\\\twig_get_attribute',
                    $content
                );
            }

            return $content;
        },
    ],
];
