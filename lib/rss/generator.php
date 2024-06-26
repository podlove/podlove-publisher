<?php

namespace Podlove\RSS;

use Podlove\Model;
use Sabre\Xml\Element\Cdata;

final class Generator
{
    const NS_ATOM = '{http://www.w3.org/2005/Atom}';
    const NS_ITUNES = '{http://www.itunes.com/dtds/podcast-1.0.dtd}';
    const NS_PODCAST = '{https://podcastindex.org/namespace/1.0}';
    private $podcast;

    public function __construct()
    {
        $this->podcast = Model\Podcast::get();
    }

    public function generate(): void
    {
        $service = new \Sabre\Xml\Service();
        $service->namespaceMap = [
            'http://www.w3.org/2005/Atom' => 'atom',
            'http://www.itunes.com/dtds/podcast-1.0.dtd' => 'itunes',
            'https://podcastindex.org/namespace/1.0' => 'podcast'
        ];

        $xml = $service->write('rss', new Element\RSS([
            'channel' => [
                ...$this->channel(),
                ...$this->items()
            ]
        ]));

        header('Content-Type: application/rss+xml; charset=UTF-8', true);
        echo $xml;
        exit;
    }

    private function channel()
    {
        // TODO
        // - <image> (url, title, link)
        // - <atom:link> Pagination
        // - <language>
        // - <fyyd:verify>
        // - <podcast:funding>
        // - <podcast:license>
        // - <itunes:category>
        // - <itunes:owner>
        // - <itunes:image>
        // - <itunes:subtitle>
        // - <itunes:block>
        // - <itunes:explicit>
        $channel = [
            'title' => apply_filters('podlove_feed_title', ''),
            'link' => apply_filters('podlove_feed_link', \Podlove\get_landing_page_url()),
            'description' => new Cdata($this->podcast->summary),
            'lastBuildDate' => mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false),
            'generator' => \Podlove\get_plugin_header('Name').' v'.\Podlove\get_plugin_header('Version'),
            'copyright' => apply_filters('podlove_feed_copyright', $this->podcast->copyright ?? $this->podcast->default_copyright_claim()),
            self::NS_ITUNES.'author' => apply_filters('podlove_feed_itunes_author', $this->podcast->author_name),
            self::NS_ITUNES.'type' => apply_filters('podlove_feed_itunes_type', in_array($this->podcast->itunes_type, ['episodic', 'serial']) ? $this->podcast->itunes_type : 'episodic'),
            self::NS_ITUNES.'summary' => apply_filters('podlove_feed_itunes_summary', $this->podcast->summary),
        ];

        return apply_filters('podlove_rss_channel', $channel);
    }

    // NOTE: This uses/requires The WordPress Loop
    private function items()
    {
        $items = [];

        // TODO: guard that this is != null
        $feed = \Podlove\Feeds\get_feed();

        while (have_posts()) {
            the_post();

            $post = \get_post();
            $episode = Model\Episode::find_one_by_post_id($post->ID);
            $asset = $feed->episode_asset();
            $file_type = $asset->file_type();
            $file = Model\MediaFile::find_by_episode_id_and_episode_asset_id($episode->id, $asset->id);

            // skip episode if there is no valid file
            if (!$file) {
                continue;
            }

            // TODO
            // - <description>
            // - <itunes:author> -- not in spec => remove? seems pointless as we do not use episodic data anyway
            // - <itunes:subtitle>
            // - <itunes:summary>
            // - <itunes:image>
            // - <content:encoded>
            // - <podcast:transcript>
            // - caching per item

            $item = [
                'name' => 'item',
                'value' => [
                    'title' => \Podlove\Feeds\get_episode_title(),
                    'link' => get_permalink(),
                    'pubDate' => mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false),
                    'guid' => [
                        'attributes' => ['isPermalink' => 'false'],
                        'value' => get_the_guid()
                    ],
                    ...$this->enclosure($episode, $file, $asset, $feed, $file_type),
                    ...$this->deep_link($episode),
                    ...$this->itunes_duration($episode),
                    ...$this->itunes_title($episode),
                    ...$this->itunes_episode($episode),
                    ...$this->itunes_episode_type($episode),
                ]
            ];

            $item = apply_filters('podlove_rss_item', $item, $episode, $feed);
            $items[] = $item;
        }

        return $items;
    }

    private function enclosure(Model\Episode $episode, $file, $asset, $feed, $file_type)
    {
        $is_tracking_disabled = isset($_REQUEST['tracking']) && $_REQUEST['tracking'] == 'no';
        $url = $is_tracking_disabled
          ? $episode->enclosure_url($asset, null, null)
          : $episode->enclosure_url($asset, 'feed', $feed->slug);

        return ['enclosure' => [
            'attributes' => [
                'url' => $url,
                'length' => (string) ($file->size > 0 ? $file->size : 0),
                'type' => $file_type->mime_type
            ]
        ]];
    }

    private function deep_link(Model\Episode $episode)
    {
        return [[
            'name' => self::NS_ATOM.'link',
            'attributes' => [
                'rel' => 'http://podlove.org/deep-link',
                'href' => get_permalink($episode->post_id).'#'
            ]
        ]];
    }

    private function itunes_duration(Model\Episode $episode)
    {
        return [[
            'name' => self::NS_ITUNES.'duration',
            'value' => $episode->get_duration('HH:MM:SS')
        ]];
    }

    private function itunes_title(Model\Episode $episode)
    {
        if (!$episode->title) {
            return [];
        }

        return [[
            'name' => self::NS_ITUNES.'title',
            'value' => trim($episode->title)
        ]];
    }

    private function itunes_episode(Model\Episode $episode)
    {
        if (!is_numeric($episode->number)) {
            return [];
        }

        // TODO: think about if I should at least take over the value filters, like podlove_feed_itunes_episode
        return [[
            'name' => self::NS_ITUNES.'episode',
            'value' => (string) $episode->number
        ]];
    }

    private function itunes_episode_type(Model\Episode $episode)
    {
        $type = in_array($episode->type, ['full', 'trailer', 'bonus']) ? $episode->type : 'full';

        return [[
            'name' => self::NS_ITUNES.'episodeType',
            'value' => $type
        ]];
    }
}
