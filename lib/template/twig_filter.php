<?php

namespace Podlove\Template;

use Podlove\Model;
use PodlovePublisher_Vendor\Twig;

/**
 * Apply Twig functionality and podcast/episode accessors to strings/templates.
 *
 * Example:
 *     add_filter('some_filter_for_a_string', array('\Podlove\Template\TwigFilter', 'apply_to_html'));
 *
 * @param string $html HTML string
 * @param array  $vars optional map of template variables
 */
class TwigFilter
{
    public static $template_tags = [
        'is_archive',
        'is_post_type_archive',
        'is_attachment',
        'is_tax',
        'is_date',
        'is_day',
        'is_feed',
        'is_comment_feed',
        'is_front_page',
        'is_home',
        'is_month',
        'is_page',
        'is_paged',
        'is_preview',
        'is_search',
        'is_single',
        'is_singular',
        'is_time',
        'is_year',
        'is_404',
        'is_main_query',
    ];

    /**
     * Apply Twig to given template.
     *
     * @param string $html file path or HTML string
     * @param array  $vars optional variables for Twig context
     *
     * @return string rendered template string
     */
    public static function apply_to_html($html, $vars = [])
    {
        $twig = self::getTwigEnv();

        $context = ['option' => $vars];

        // add podcast to global context
        $context = array_merge(
            $context,
            ['podcast' => new Podcast(Model\Podcast::get())]
        );

        // Apply filters to twig templates
        $context = apply_filters('podlove_templates_global_context', $context);

        // add podcast to global context if we are in an episode
        if ($episode = Model\Episode::find_one_by_property('post_id', get_the_ID())) {
            $context = array_merge($context, ['episode' => new Episode($episode)]);
        }

        $result = null;

        if ($twig->getLoader()->exists($html)) {
            try {
                $result = $twig->render($html, $context);
            } catch (Twig\Error\Error $e) {
                $message = $e->getRawMessage();
                $line = $e->getTemplateLine();
                $template = $e->getSourceContext()->getName();

                $result = 'Twig Error: '.$message.' (in template "'.$template.'" line '.$line.')';

                \Podlove\Log::get()->addError($message, [
                    'type' => 'twig',
                    'line' => $line,
                    'template' => $template,
                ]);
            }
        }

        if ($result === null) {
            try {
                // simple Twig Env to render plain string
                $env = new Twig\Environment(new Twig\Loader\ArrayLoader([]), ['autoescape' => false]);

                // no clue yet how this is possible but it happens
                if (method_exists($env, 'createTemplate')) {
                    $template = $env->createTemplate($html);
                    $result = $template->render($context);
                } else {
                    \Podlove\Log::get()->addError('Error when rendering Twig template from string. Missing Twig_Environment::createTemplate method.', [
                        'type' => 'twig',
                        'template' => $html,
                    ]);
                }
            } catch (\Exception $e) {
                \Podlove\Log::get()->addError('Error when rendering Twig template from string: '.$e->getMessage(), [
                    'type' => 'twig',
                    'template' => $html,
                ]);
            }
        }

        return $result;
    }

    private static function getTwigLoader()
    {
        // file loader for internal use
        $file_loader = new Twig\Loader\FilesystemLoader();
        $file_loader->addPath(implode(DIRECTORY_SEPARATOR, [\Podlove\PLUGIN_DIR, 'templates']), 'core');

        // other modules can register their own template directories/namespaces
        $file_loader = apply_filters('podlove_twig_file_loader', $file_loader);

        // database loader for user templates
        $db_loader = new TwigLoaderPodloveDatabase();

        $loaders = [$file_loader, $db_loader];
        $loaders = apply_filters('podlove_twig_loaders', $loaders);

        return new Twig\Loader\ChainLoader($loaders);
    }

    private static function getTwigEnv()
    {
        $twig = new Twig\Environment(self::getTwigLoader(), ['autoescape' => false]);
        $twig->addExtension(new DateExtension());

        $formatBytesFilter = new Twig\TwigFilter('formatBytes', function ($string) {
            return \Podlove\format_bytes($string, 0);
        });

        $padLeftFilter = new Twig\TwigFilter('padLeft', function ($string, $padChar, $length) {
            while (strlen($string) < $length) {
                $string = $padChar.$string;
            }

            return $string;
        });

        $wpautopFilter = new Twig\TwigFilter('wpautop', function ($content) {
            return \wpautop($content);
        });

        $twig->addFilter($formatBytesFilter);
        $twig->addFilter($padLeftFilter);
        $twig->addFilter($wpautopFilter);

        // add functions
        foreach (self::$template_tags as $tag) {
            $func = new Twig\TwigFunction($tag, $tag);
            $twig->addFunction($func);
        }

        $func = new Twig\TwigFunction('get_the_post_thumbnail_url', function ($post = null, $size = 'post-thumbnail') {
            return get_the_post_thumbnail_url($post, $size);
        });
        $twig->addFunction($func);

        // shortcode_exists
        $func = new Twig\TwigFunction('shortcode_exists', function ($shortcode) {
            return \shortcode_exists($shortcode);
        });
        $twig->addFunction($func);

        // Translation functions
        $twig->addFunction(new Twig\TwigFunction('__', function ($text, $domain = 'default') {
            return \__($text, $domain);
        }));

        $twig->addFunction(new Twig\TwigFunction('_x', function ($text, $context, $domain = 'default') {
            return \_x($text, $context, $domain);
        }));

        $twig->addFunction(new Twig\TwigFunction('_n', function ($single, $plural, $number, $domain = 'default') {
            return \_n($single, $plural, $number, $domain);
        }));

        $twig->addFunction(new Twig\TwigFunction('_nx', function ($single, $plural, $number, $context, $domain = 'default') {
            return \_x($single, $plural, $number, $context, $domain);
        }));

        $allowed_custom_function_names = [
            'get_the_post_thumbnail_url',
            'shortcode_exists',
            '__',
            '_x',
            '_n',
            '_nx'
        ];

        $allowed_custom_filters = [
            'formatBytes',
            'padLeft',
            'wpautop',
        ];

        $twig_tags = [
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

        $twig_filters = [
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

        $twig_functions = [
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

        // WIP: dynamic template accessors

        $dynamicAccessorClasses = [
            '\Podlove\Modules\Contributors\TemplateExtensions',
            '\Podlove\Modules\Seasons\TemplateExtensions',
            '\Podlove\Modules\RelatedEpisodes\TemplateExtensions',
            '\Podlove\Modules\Shows\TemplateExtensions',
            '\Podlove\Modules\Social\TemplateExtensions',
            '\Podlove\Modules\SubscribeButton\TemplateExtensions',
            '\Podlove\Modules\Transcripts\TemplateExtensions',
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
                };

                if (!isset($dynamicAccessors[$class])) {
                    $dynamicAccessors[$class] = [];
                }

                $dynamicAccessors[$class][] = $method['methodname'];
            }
        }

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

        $podlove_template_methods = array_reduce($classes, function ($agg, $class) use ($dynamicAccessors) {
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

        // END playground

        // BEGIN security

        $filters = array_merge(
            $twig_filters,
            $allowed_custom_filters
        );
        $methods = $podlove_template_methods;
        $properties = [];
        $functions = array_merge(
            $twig_functions,
            self::$template_tags,
            $allowed_custom_function_names
        );
        $policy = new Twig\Sandbox\SecurityPolicy($twig_tags, $filters, $methods, $properties, $functions);

        $twig->addExtension(new Twig\Extension\SandboxExtension($policy, true));

        // END security

        return $twig;
    }
}
