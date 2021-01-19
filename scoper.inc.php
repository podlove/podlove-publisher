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

            if (stristr($filePath, 'CoreExtension.php') || stristr($filePath, 'EscaperExtension.php') || stristr($filePath, 'DebugExtension.php')) {
                $pattern = '/TwigFilter\((\'[^\']+\'),\s+\'(_?twig[^\']+)\'/';
                $content = preg_replace_callback(
                    $pattern,
                    function ($matches) use ($prefix) {
                        return 'TwigFilter('.$matches[1].', \''.$prefix.'\\'.$matches[2].'\'';
                    },
                    $content
                );

                $pattern = '/TwigFunction\((\'[^\']+\'),\s+\'(twig[^\']+)\'/';
                $content = preg_replace_callback(
                    $pattern,
                    function ($matches) use ($prefix) {
                        return 'TwigFunction('.$matches[1].', \''.$prefix.'\\'.$matches[2].'\'';
                    },
                    $content
                );

                $pattern = '/TwigTest\((\'[^\']+\'),\s+\'(twig[^\']+)\'/';
                $content = preg_replace_callback(
                    $pattern,
                    function ($matches) use ($prefix) {
                        return 'TwigTest('.$matches[1].', \''.$prefix.'\\'.$matches[2].'\'';
                    },
                    $content
                );
            }

            if (stristr($filePath, 'ForNode.php')) {
                $content = str_replace(
                    ' = twig_ensure_traversable',
                    ' = '.$prefix.'\\\\twig_ensure_traversable',
                    $content
                );
            }

            if (stristr($filePath, 'IncludeNode.php') || stristr($filePath, 'WithNode.php')) {
                $content = str_replace(
                    'twig_array_merge(',
                    $prefix.'\\\\twig_array_merge(',
                    $content
                );
                $content = str_replace(
                    'twig_to_array(',
                    $prefix.'\\\\twig_to_array(',
                    $content
                );
                $content = str_replace(
                    'twig_test_iterable(',
                    $prefix.'\\\\twig_test_iterable(',
                    $content
                );
            }

            if (stristr($filePath, 'InBinary.php')) {
                $content = str_replace(
                    'twig_in_filter(',
                    $prefix.'\\\\twig_in_filter(',
                    $content
                );
            }

            if (stristr($filePath, 'MethodCallExpression.php')) {
                $content = str_replace(
                    'twig_call_macro(',
                    $prefix.'\\\\twig_call_macro(',
                    $content
                );
            }

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
