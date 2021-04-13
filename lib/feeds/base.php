<?php

namespace Podlove\Feeds;

use Podlove\Model;

function get_description()
{
    global $post;

    $episode = \Podlove\Model\Episode::find_one_by_post_id($post->ID);

    $summary = trim($episode->summary);
    $subtitle = trim($episode->subtitle);
    $title = trim($post->post_title);

    $description = '';

    if (strlen($summary)) {
        $description = $summary;
    } elseif (strlen($subtitle)) {
        $description = $subtitle;
    } else {
        $description = $title;
    }

    return apply_filters('podlove_feed_item_description', $description);
}

function override_feed_title($feed)
{
    add_filter('podlove_feed_title', function ($title) use ($feed) {
        return htmlspecialchars($feed->get_title());
    });
}

function override_feed_description($feed)
{
    add_filter('podlove_rss_feed_description', function ($description) {
        $podcast = Model\Podcast::get();

        if ($podcast->subtitle) {
            $desc = $podcast->subtitle;
        } elseif ($podcast->summary) {
            $desc = $podcast->summary;
        } else {
            $desc = $description;
        }

        return get_xml_cdata_text($desc);
    });
}

function override_feed_language($feed)
{
    add_filter('pre_option_rss_language', function ($language) {
        $podcast = Model\Podcast::get();

        return apply_filters('podlove_feed_language', ($podcast->language) ? $podcast->language : $language);
    });
}

function get_episode_title($post = 0)
{
    $post = get_post($post);
    $title = $post->post_title ?? '';

    return apply_filters('podlove_get_episode_title_rss', $title);
}

/**
 * Prepare content for display in feed.
 *
 * - Trim whitespace
 * - Convert special characters to HTML entities
 *
 * @param string $content
 *
 * @return string
 */
function prepare_for_feed($content)
{
    return trim(htmlspecialchars($content));
}

function get_xml_text_node($tag_name, $content)
{
    $doc = new \DOMDocument();
    $node = $doc->createElement($tag_name);
    $text = $doc->createTextNode($content);
    $node->appendChild($text);

    return $doc->saveXML($node);
}

function get_xml_cdata_node($tag_name, $content)
{
    $doc = new \DOMDocument();
    $node = $doc->createElement($tag_name);
    $text = $doc->createCDATASection($content);
    $node->appendChild($text);

    return $doc->saveXML($node);
}

function get_xml_cdata_text($content)
{
    $doc = new \DOMDocument();
    $text = $doc->createCDATASection($content);

    return $doc->saveXML($text);
}

function get_xml_itunesimage_node($url)
{
    $doc = new \DOMDocument();
    $node = $doc->createElement('itunes:image');

    $attr = $doc->createAttribute('href');

    // unexpected but true: ampersands are not escaped automatically here
    $attr->value = esc_attr($url);

    $node->appendChild($attr);

    return $doc->saveXML($node);
}

function get_xml_podcast_funding_node($url, $label)
{
    $doc = new \DOMDocument();
    $node = $doc->createElement('podcast:funding');
    $text = $doc->createTextNode($label);
    $node->appendChild($text);

    $attr = $doc->createAttribute('url');

    // unexpected but true: ampersands are not escaped automatically here
    $attr->value = esc_attr($url);

    $node->appendChild($attr);

    return $doc->saveXML($node);
}

function override_feed_head($hook, $podcast, $feed, $format)
{
    add_filter('podlove_feed_content', '\Podlove\Feeds\prepare_for_feed');

    remove_action($hook, 'the_generator');
    add_action($hook, function () use ($hook) {
        switch ($hook) {
            case 'rss2_head':
                $gen = '<generator>'.\Podlove\get_plugin_header('Name').' v'.\Podlove\get_plugin_header('Version').'</generator>';

                break;

            case 'atom_head':
                $gen = '<generator uri="'.\Podlove\get_plugin_header('PluginURI').'" version="'.\Podlove\get_plugin_header('Version').'">'.\Podlove\get_plugin_header('Name').'</generator>';

                break;
        }
        echo $gen;
    });

    add_action($hook, function () use ($feed) {
        echo $feed->get_feed_self_link();
        echo $feed->get_alternate_links();
    }, 9);

    // add rss image
    add_action($hook, function () use ($podcast, $hook) {
        $image = [
            'url' => apply_filters('podlove_feed_itunes_image_url', $podcast->cover_art()->url()),
            'title' => $podcast->title,
            'link' => apply_filters('podlove_feed_link', \Podlove\get_landing_page_url()),
        ];
        $image = apply_filters('podlove_feed_image', $image);

        if (!$image['url']) {
            return;
        }

        // remove WordPress provided favicon
        remove_action($hook, 'rss2_site_icon');

        // generate our own image tag
        $dom = new \Podlove\DomDocumentFragment();
        $image_tag = $dom->createElement('image');

        foreach ($image as $tag_name => $tag_text) {
            if ($tag_text) {
                $tag = $dom->createElement($tag_name);
                $tag_text = $dom->createTextNode($tag_text);
                $tag->appendChild($tag_text);
                $image_tag->appendChild($tag);
            }
        }

        $dom->appendChild($image_tag);

        echo (string) $dom;
    }, 5); // let it run early so we can stop the `rss2_site_icon` call

    add_action($hook, function () use ($podcast, $feed, $format) {
        echo PHP_EOL;

        $author = "\t".get_xml_text_node('itunes:author', $podcast->author_name);
        echo apply_filters('podlove_feed_itunes_author', $author);
        echo PHP_EOL;

        $type = in_array($podcast->itunes_type, ['episodic', 'serial']) ? $podcast->itunes_type : 'episodic';
        $type = "\t".get_xml_text_node('itunes:type', $type);
        echo apply_filters('podlove_feed_itunes_type', $type);
        echo PHP_EOL;

        $summary = "\t".get_xml_cdata_node('itunes:summary', $podcast->summary);
        echo apply_filters('podlove_feed_itunes_summary', $summary);
        echo PHP_EOL;

        $categories = \Podlove\Itunes\categories(false);
        $category_html = '';
        $category_id = $podcast->category_1;
        $category_id = apply_filters('podlove_feed_itunes_category_id', $category_id);

        if ($category_id) {
            list($cat, $subcat) = explode('-', $category_id);

            if ($subcat == '00') {
                $category_html .= sprintf(
                    '<itunes:category text="%s" />',
                    htmlspecialchars($categories[$category_id])
                );
            } else {
                if ($categories[$category_id]) {
                    $category_html .= sprintf(
                        '<itunes:category text="%s"><itunes:category text="%s"></itunes:category></itunes:category>',
                        htmlspecialchars($categories[$cat.'-00']),
                        htmlspecialchars($categories[$category_id])
                    );
                } else {
                    $category_html .= sprintf(
                        '<itunes:category text="%s" />',
                        htmlspecialchars($categories[$cat.'-00'])
                    );
                }
            }
        }
        echo apply_filters('podlove_feed_itunes_categories', $category_html);
        echo PHP_EOL;

        $owner = '
	<itunes:owner>
		'.get_xml_text_node('itunes:name', $podcast->owner_name).'
		'.get_xml_text_node('itunes:email', $podcast->owner_email).'
	</itunes:owner>';
        echo "\t".apply_filters('podlove_feed_itunes_owner', $owner);
        echo PHP_EOL;

        if ($cover_art_url = $podcast->cover_art()->url()) {
            $coverimage = get_xml_itunesimage_node($cover_art_url);
        } else {
            $coverimage = '';
        }
        echo "\t".apply_filters('podlove_feed_itunes_image', $coverimage);
        echo PHP_EOL;

        $subtitle = get_xml_text_node('itunes:subtitle', $podcast->subtitle);
        echo "\t".apply_filters('podlove_feed_itunes_subtitle', $subtitle);
        echo PHP_EOL;

        $block = sprintf('<itunes:block>%s</itunes:block>', ($feed->enable) ? 'no' : 'yes');
        echo "\t".apply_filters('podlove_feed_itunes_block', $block);
        echo PHP_EOL;

        $explicit = sprintf('<itunes:explicit>%s</itunes:explicit>', ($podcast->explicit == 2) ? 'clean' : (($podcast->explicit) ? 'yes' : 'no'));
        echo "\t".apply_filters('podlove_feed_itunes_explicit', $explicit);
        echo PHP_EOL;

        $complete = sprintf('<itunes:complete>%s</itunes:complete>', ($podcast->complete) ? 'yes' : 'no');
        echo "\t".apply_filters('podlove_feed_itunes_complete', ($podcast->complete ? "\t{$complete}" : ''));
        echo PHP_EOL;

        $itunes_feed_id = (int) $feed->itunes_feed_id;
        if ($itunes_feed_id > 0) {
            $link_apple = sprintf('<atom:link rel="me" href="https://podcasts.apple.com/podcast/id%s" />', $itunes_feed_id);
            echo "\t".apply_filters('podlove_feed_link_apple', $link_apple);
            echo PHP_EOL;
        }

        if ($podcast->funding_url) {
            echo "\t".get_xml_podcast_funding_node($podcast->funding_url, $podcast->funding_label);
            echo PHP_EOL;
        }

        do_action('podlove_append_to_feed_head', $podcast, $feed, $format);
    });
}

function override_feed_entry($hook, $podcast, $feed, $format)
{
    add_action($hook, function () use ($podcast, $feed, $format) {
        global $post;

        $cache = \Podlove\Cache\TemplateCache::get_instance();
        echo $cache->cache_for('feed_item_'.$feed->slug.'_'.$post->ID, function () use ($podcast, $feed, $format, $post) {
            $xml = '';

            $episode = Model\Episode::find_one_by_post_id($post->ID);
            $asset = $feed->episode_asset();
            $file = Model\MediaFile::find_by_episode_id_and_episode_asset_id($episode->id, $asset->id);
            $asset_assignment = Model\AssetAssignment::get_instance();

            if (!$file) {
                return;
            }

            $enclosure_file_size = $file->size;

            $cover_art_url = '';
            if ($cover_art = $episode->cover_art()) {
                $cover_art_url = $cover_art->url();
            }

            if (isset($_REQUEST['tracking']) && $_REQUEST['tracking'] == 'no') {
                $enclosure_url = $episode->enclosure_url($feed->episode_asset(), null, null);
            } else {
                $enclosure_url = $episode->enclosure_url($feed->episode_asset(), 'feed', $feed->slug);
            }

            $tag_prefix = "\n\t\t";

            $deep_link = Model\Feed::get_link_tag([
                'prefix' => 'atom',
                'rel' => 'http://podlove.org/deep-link',
                'type' => '',
                'title' => '',
                'href' => get_permalink().'#',
            ]);
            $xml .= $tag_prefix.apply_filters('podlove_deep_link', $deep_link, $feed);

            $xml .= $tag_prefix.apply_filters('podlove_feed_enclosure', '', $enclosure_url, $enclosure_file_size, $format->mime_type, $file);

            $duration = sprintf('<itunes:duration>%s</itunes:duration>', $episode->get_duration('HH:MM:SS'));
            $xml .= $tag_prefix.apply_filters('podlove_feed_itunes_duration', $duration);

            $author = apply_filters('podlove_feed_content', $podcast->author_name);
            $author = get_xml_text_node('itunes:author', $author);
            $xml .= $tag_prefix.apply_filters('podlove_feed_itunes_author', $author);

            $subtitle = apply_filters('podlove_feed_content', \Podlove\PHP\escape_shortcodes($episode->subtitle));
            $subtitle = get_xml_text_node('itunes:subtitle', $subtitle);
            $xml .= $tag_prefix.apply_filters('podlove_feed_itunes_subtitle', $subtitle);

            if ($episode->title) {
                $title = apply_filters('podlove_feed_itunes_title', $episode->title);
                $title = get_xml_text_node('itunes:title', $title);
                $xml .= $tag_prefix.apply_filters('podlove_feed_itunes_title_xml', $title);
            }

            if (is_numeric($episode->number)) {
                $number = apply_filters('podlove_feed_itunes_episode', (int) $episode->number);
                $number = sprintf('<itunes:episode>%s</itunes:episode>', $number);
                $xml .= $tag_prefix.apply_filters('podlove_feed_itunes_episode_xml', $number);
            }

            $type = in_array($episode->type, ['full', 'trailer', 'bonus']) ? $episode->type : 'full';
            $type = apply_filters('podlove_feed_itunes_type', $type);
            $type = sprintf('<itunes:episodeType>%s</itunes:episodeType>', $type);
            $xml .= $tag_prefix.apply_filters('podlove_feed_itunes_type_xml', $type);

            $summary = apply_filters('podlove_feed_content', \Podlove\PHP\escape_shortcodes($episode->summary));
            if (strlen($summary)) {
                $summary = get_xml_cdata_node('itunes:summary', $summary);
            }
            $xml .= $tag_prefix.apply_filters('podlove_feed_itunes_summary', $summary);

            if (\Podlove\get_setting('metadata', 'enable_episode_explicit')) {
                $itunes_explicit = apply_filters('podlove_feed_content', $episode->explicit_text());
                $itunes_explicit = sprintf('<itunes:explicit>%s</itunes:explicit>', $itunes_explicit);
                $xml .= $tag_prefix.apply_filters('podlove_feed_itunes_explicit', $itunes_explicit);
            }

            if ($cover_art_url) {
                $cover_art = get_xml_itunesimage_node($cover_art_url);
            } else {
                $cover_art = '';
            }
            $xml .= $tag_prefix.apply_filters('podlove_feed_episode_itunes_image', $cover_art);

            if ($feed->embed_content_encoded) {
                add_filter('the_content_feed', function ($content, $feed_type) {
                    return preg_replace('#<style(.*?)>(.*?)</style>#is', '', $content);
                }, 10, 2);
                $content_encoded = get_xml_cdata_node('content:encoded', get_the_content_feed('rss2'));
                $xml .= $tag_prefix.apply_filters('podlove_feed_content_encoded', $content_encoded);
            }

            ob_start();
            do_action('podlove_append_to_feed_entry', $podcast, $episode, $feed, $format);
            $xml .= ob_get_contents();
            ob_end_clean();

            return $xml;
        }, 15 * MINUTE_IN_SECONDS);
    }, 11);
}
