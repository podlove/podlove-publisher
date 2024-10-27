<?php

namespace Podlove\Template;

use PodlovePublisher_Vendor\Twig;

/**
 * Configuration for Twig Sandbox.
 *
 * - allows all default Twig tags, filters and functions
 * - adds custom filters and functions which are defined in TwigFilter
 * - auto-generates allowlist for the Podlove Template API using reflection
 */
class TwigSandbox
{
    public static $allowed_custom_function_names = [
        'get_the_post_thumbnail_url',
        'shortcode_exists',
        '__',
        '_x',
        '_n',
        '_nx'
    ];

    public static $allowed_custom_filters = [
        'formatBytes',
        'padLeft',
        'wpautop',
    ];

    public static $twig_tags = [
        'apply',
        'autoescape',
        'block',
        'cache',
        'deprecated',
        'do',
        'embed',
        'extends',
        'flush',
        'for',
        'from',
        'guard',
        'if',
        'import',
        'include',
        'macro',
        'sandbox',
        'set',
        'types',
        'use',
        'verbatim',
        'with',
    ];

    public static $twig_filters = [
        'abs',
        'batch',
        'capitalize',
        'column',
        'convert_encoding',
        'country_name',
        'currency_name',
        'currency_symbol',
        'data_uri',
        'date',
        'date_modify',
        'default',
        'escape',
        'filter',
        'find',
        'first',
        'format',
        'format_currency',
        'format_date',
        'format_datetime',
        'format_number',
        'format_time',
        'html_to_markdown',
        'inky_to_html',
        'inline_css',
        'join',
        'json_encode',
        'keys',
        'language_name',
        'last',
        'length',
        'locale_name',
        'lower',
        'map',
        'markdown_to_html',
        'merge',
        'nl2br',
        'number_format',
        'plural',
        'raw',
        'reduce',
        'replace',
        'reverse',
        'round',
        'shuffle',
        'singular',
        'slice',
        'slug',
        'sort',
        'spaceless',
        'split',
        'striptags',
        'timezone_name',
        'title',
        'trim',
        'u',
        'upper',
        'url_encode',
    ];

    public static $twig_functions = [
        'attribute',
        'block',
        'constant',
        'country_names',
        'country_timezones',
        'currency_names',
        'cycle',
        'date',
        'dump',
        'enum',
        'enum_cases',
        'html_classes',
        'html_cva',
        'include',
        'language_names',
        'locale_names',
        'max',
        'min',
        'parent',
        'random',
        'range',
        'script_names',
        'source',
        'template_from_string',
        'timezone_names',
    ];

    public static $wp_post_props = ['WP_Post' => [
        'ID',
        'post_author',
        'post_date',
        'post_date_gmt',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'comment_status',
        'ping_status',
        'post_name',
        'to_ping',
        'pinged',
        'post_modified',
        'post_modified_gmt',
        'post_parent',
        'guid',
        'menu_order',
        'post_type',
        'post_mime_type',
        'comment_count'
    ]];

    public static function getSecurityPolicy()
    {
        $filters = array_merge(
            self::$twig_filters,
            self::$allowed_custom_filters
        );
        $methods = self::get_podlove_template_methods();
        $properties = self::$wp_post_props;
        $functions = array_merge(
            self::$twig_functions,
            TwigFilter::$template_tags,
            self::$allowed_custom_function_names
        );

        return new Twig\Sandbox\SecurityPolicy(self::$twig_tags, $filters, $methods, $properties, $functions);
    }

    private static function get_podlove_template_methods()
    {
        $classes = [
            '\Podlove\Template\Podcast',
            '\Podlove\Template\Feed',
            '\Podlove\Template\Episode',
            '\Podlove\Template\EpisodeTitle',
            '\Podlove\Template\Asset',
            '\Podlove\Template\File',
            '\Podlove\Template\Duration',
            '\Podlove\Template\Chapter',
            '\Podlove\Template\License',
            '\Podlove\Template\DateTime',
            '\Podlove\Template\FileType',
            '\Podlove\Template\Tag',
            '\Podlove\Template\Category',
            '\Podlove\Template\Image',
            '\Podlove\Modules\Contributors\Template\Avatar',
            '\Podlove\Modules\Contributors\Template\Contributor',
            '\Podlove\Modules\Contributors\Template\ContributorGroup',
            '\Podlove\Modules\Seasons\Template\Season',
            '\Podlove\Modules\Shows\Template\Show',
            '\Podlove\Modules\Social\Template\Service',
            '\Podlove\Modules\Networks\Template\Network',
            '\Podlove\Modules\Networks\Template\PodcastList',
            '\Podlove\Modules\Transcripts\Template\Line',
            '\Podlove\Modules\Transcripts\Template\Group',
        ];

        $dynamicAccessorClasses = [
            '\Podlove\Modules\Contributors\TemplateExtensions',
            '\Podlove\Modules\Seasons\TemplateExtensions',
            '\Podlove\Modules\RelatedEpisodes\TemplateExtensions',
            '\Podlove\Modules\Shows\TemplateExtensions',
            '\Podlove\Modules\Social\TemplateExtensions',
            '\Podlove\Modules\SubscribeButton\TemplateExtensions',
            '\Podlove\Modules\Transcripts\TemplateExtensions',
            '\Podlove\Modules\Shownotes\TemplateExtensions'
        ];

        $dynamicAccessors = [];
        foreach ($dynamicAccessorClasses as $class) {
            $reflectionClass = new \ReflectionClass($class);
            $methods = $reflectionClass->getMethods();

            $accessors = array_filter($methods, function ($method) {
                $comment = $method->getDocComment();

                return stripos($comment, '@accessor') !== false && stripos($comment, '@dynamicAccessor') !== false;
            });

            $parsedMethods = array_map(function ($method) {
                $c = new \Podlove\Comment\Comment($method->getDocComment());
                $c->parse();

                $dynamicAccessor = $c->getTag('dynamicAccessor');
                $callData = explode('.', $dynamicAccessor['description']);

                return [
                    'methodname' => $callData[1],
                    'class' => $callData[0],
                ];
            }, $accessors);

            foreach ($parsedMethods as $method) {
                $class = match ($method['class']) {
                    'episode' => 'Podlove\Template\Episode',
                    'podcast' => 'Podlove\Template\Podcast',
                    'contributor' => 'Podlove\Modules\Contributors\Template\Contributor',
                    default => null
                };

                if ($class) {
                    if (!isset($dynamicAccessors[$class])) {
                        $dynamicAccessors[$class] = [];
                    }

                    $dynamicAccessors[$class][] = $method['methodname'];
                }
            }
        }

        return array_reduce($classes, function ($agg, $class) use ($dynamicAccessors) {
            $reflectionClass = new \ReflectionClass($class);
            $className = $reflectionClass->getName();
            $methods = $reflectionClass->getMethods();

            $accessors = array_filter($methods, function ($method) {
                $comment = $method->getDocComment();

                return stripos($comment, '@accessor') !== false;
            });

            $parsedMethods = array_map(function ($method) {
                return $method->name;
            }, $accessors);

            if (isset($dynamicAccessors[$className])) {
                foreach ($dynamicAccessors[$className] as $dynamicMethod) {
                    $parsedMethods[] = $dynamicMethod;
                }
            }

            $agg[$className] = array_values($parsedMethods);

            return $agg;
        }, []);
    }
}
