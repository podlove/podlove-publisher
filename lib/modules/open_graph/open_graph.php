<?php

namespace Podlove\Modules\OpenGraph;

use Podlove\DomDocumentFragment;
use Podlove\Model;

class Open_Graph extends \Podlove\Modules\Base
{
    protected $module_name = 'Open Graph Integration';
    protected $module_description = 'Adds Open Graph metadata to episodes. Useful for third party services.';
    protected $module_group = 'web publishing';

    public function load()
    {
        add_action('wp', [$this, 'register_hooks']);
    }

    /**
     * Register hooks on episode pages only.
     */
    public function register_hooks()
    {
        // wpseo creates its own tags
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            return;
        }

        // all in one seo creates its own tags
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            return;
        }

        // wpseo creates its own tags
        if (defined('WPSEODE_BASE')) {
            return;
        }

        if (!is_single()) {
            return;
        }

        if ('podcast' !== get_post_type()) {
            return;
        }

        add_filter('language_attributes', function ($output = '') {
            return $output.' prefix="og: http://ogp.me/ns#"';
        });

        // as recommended in http://jetpack.me/2013/05/03/remove-open-graph-meta-tags/
        // @fixme Generate conflicts for known conflicting plugins.
        //        Get inspired by Jetpack's list class.jetpack.php "open_graph_conflicting_plugins"
        add_filter('jetpack_enable_open_graph', '__return_false');

        add_action('wp_head', [$this, 'the_open_graph_metadata']);
    }

    public function the_open_graph_metadata()
    {
        $cache_key = 'opgv2'.get_the_ID().get_permalink();

        $cache = \Podlove\Cache\TemplateCache::get_instance();
        echo $cache->cache_for($cache_key, function () {
            return (string) \Podlove\Modules\OpenGraph\Open_Graph::get_open_graph_metadata();
        });
    }

    /**
     * Insert HTML meta tags into site head.
     *
     * @todo  caching
     * @todo  let user choose what's in og:description: subtitle, excerpt, ...
     * @todo  handle multiple releases per episode
     */
    public static function get_open_graph_metadata()
    {
        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }

        $post = get_post($post_id);

        $episode = \Podlove\Model\Episode::find_one_by_post_id($post_id);
        if (!$episode) {
            return;
        }

        $podcast = Model\Podcast::get();
        $cover_art_url = $episode->cover_art_with_fallback()->url();

        // determine featured image (thumbnail)
        $thumbnail = null;
        if (has_post_thumbnail()) {
            $post_thumbnail_id = get_post_thumbnail_id($post_id);
            $thumbnailInfo = wp_get_attachment_image_src($post_thumbnail_id);
            if (is_array($thumbnailInfo)) {
                list($thumbnail, $width, $height) = $thumbnailInfo;
            }
        }

        $description = null;
        if ($episode->summary && $episode->subtitle) {
            $description = $episode->subtitle."\n".$episode->summary;
        } elseif ($episode->summary) {
            $description = $episode->summary;
        } elseif ($episode->subtitle) {
            $description = $episode->subtitle;
        }

        // define meta tags
        $data = [
            [
                'property' => 'og:type',
                'content' => 'website',
            ],
            [
                'property' => 'og:site_name',
                'content' => ($podcast->title) ? $podcast->title : get_the_title(),
            ],
            [
                'property' => 'og:title',
                'content' => $post->post_title,
            ],
            [
                'property' => 'og:url',
                'content' => get_permalink(),
            ],
        ];

        if ($description) {
            $data[] = [
                'property' => 'og:description',
                'content' => $description,
            ];
        }

        $image_url = $cover_art_url ?? $thumbnail;

        if ($image_url) {
            $data[] = apply_filters('podlove_ogp_image_data', [
                'property' => 'og:image',
                'content' => $image_url,
            ]);
        }

        foreach ($episode->media_files() as $media_file) {
            $asset = $media_file->episode_asset();
            if ($asset->downloadable && $file_type = $asset->file_type()) {
                $mime_type = $file_type->mime_type;
                if (stripos($mime_type, 'audio') !== false) {
                    $data[] = ['property' => 'og:audio', 'content' => $media_file->get_public_file_url('opengraph', 'episode')];
                    $data[] = ['property' => 'og:audio:type', 'content' => $mime_type];
                }
            }
        }

        // print meta tags
        $dom = new DomDocumentFragment();

        foreach ($data as $meta_element) {
            $element = $dom->createElement('meta');
            foreach ($meta_element as $attribute => $value) {
                $element->setAttribute($attribute, $value);
            }
            $dom->appendChild($element);
        }

        return $dom;
    }
}
