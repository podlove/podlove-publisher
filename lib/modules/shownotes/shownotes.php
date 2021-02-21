<?php

namespace Podlove\Modules\Shownotes;

use Podlove\Modules\Affiliate\Affiliate;
use Podlove\Modules\Shownotes\Model\Entry;

class Shownotes extends \Podlove\Modules\Base
{
    protected $module_name = 'Shownotes';
    protected $module_description = 'Generate and manage episode show notes. Helps you provide rich metadata for URLs. Full support for Publisher Templates.';
    protected $module_group = 'web publishing';

    public function load()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('podlove_module_was_activated_shownotes', [$this, 'was_activated']);
        add_action('rest_api_init', [$this, 'api_init']);
        add_filter('podlove_shownotes_entry', [__CLASS__, 'apply_affiliate_to_shownotes_entry']);
        add_filter('podlove_shownotes_entry', [__CLASS__, 'encode_html']);

        add_filter('podlove_twig_file_loader', function ($file_loader) {
            $file_loader->addPath(implode(DIRECTORY_SEPARATOR, [\Podlove\PLUGIN_DIR, 'lib', 'modules', 'shownotes', 'twig']), 'shownotes');

            return $file_loader;
        });

        add_shortcode('podlove-episode-shownotes', [$this, 'shownotes_shortcode']);

        \Podlove\Template\Episode::add_accessor(
            'shownotes',
            ['\Podlove\Modules\Shownotes\TemplateExtensions', 'accessorEpisodeShownotes'],
            5
        );

        \Podlove\Template\Episode::add_accessor(
            'hasShownotes',
            ['\Podlove\Modules\Shownotes\TemplateExtensions', 'accessorEpisodeHasShownotes'],
            4
        );
    }

    public static function is_visible()
    {
        if (defined('PODLOVE_MODULE_SHOWNOTES_VISBLE')) {
            return (bool) PODLOVE_MODULE_SHOWNOTES_VISBLE;
        }

        return false;
    }

    public function was_activated()
    {
        Entry::build();
    }

    public function add_meta_box()
    {
        $post_id = get_the_ID();
        $episode = \Podlove\Model\Episode::find_or_create_by_post_id($post_id);

        add_meta_box(
            // $id
            'podlove_podcast_shownotes',
            // $title
            __('Podlove Shownotes', 'podlove-podcasting-plugin-for-wordpress'),
            // $callback
            function () use ($episode) {
                $id = esc_attr($episode->id);
                echo <<<HTML
                    <div id="podlove-shownotes-app">
                        <shownotes episodeid="{$id}"></shownotes>
                    </div>
HTML;
            },
            // $page
            'podcast',
            // $context
            'normal',
            // $priority
            'low'
        );
    }

    public function api_init()
    {
        $api = new REST_API();
        $api->register_routes();
    }

    public static function apply_affiliate_to_shownotes_entry(Entry $entry)
    {
        $url = $entry->url;

        if (stripos($url, 'amazon.de') !== false) {
            $entry->affiliate_url = Affiliate::apply_amazon_de_affiliate($url);
        } elseif (stripos($url, 'thomann.de') !== false) {
            $entry->affiliate_url = Affiliate::apply_thomann_de_affiliate($url);
        }

        return $entry;
    }

    public static function encode_html(Entry $entry)
    {
        $entry->title = html_entity_decode($entry->title);
        $entry->description = html_entity_decode($entry->description);

        return $entry;
    }

    public function shownotes_shortcode($attributes)
    {
        if (!is_array($attributes)) {
            $attributes = [];
        }

        return \Podlove\Template\TwigFilter::apply_to_html('@shownotes/shownotes.twig', $attributes);
    }
}
