<?php

namespace Podlove\RSS;

use Podlove\Model;
use Sabre\Xml\Element\Cdata;

final class Generator
{
    const NS_ATOM = '{http://www.w3.org/2005/Atom}';
    const NS_ITUNES = '{http://www.itunes.com/dtds/podcast-1.0.dtd}';
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
            'http://www.itunes.com/dtds/podcast-1.0.dtd' => 'itunes'
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
        return [
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
    }

    // NOTE: This uses/requires The WordPress Loop
    private function items()
    {
        $items = [];

        while (have_posts()) {
            the_post();

            $items[] = [
                'name' => 'item',
                'value' => [
                    'title' => \Podlove\Feeds\get_episode_title(),
                    'link' => get_permalink(),
                    'pubDate' => mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false),
                    'guid' => [
                        'attributes' => ['isPermalink' => 'false'],
                        'value' => get_the_guid()
                    ]
                ]
            ];
        }

        return $items;
    }
}
