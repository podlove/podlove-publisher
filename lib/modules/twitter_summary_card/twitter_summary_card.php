<?php

namespace Podlove\Modules\TwitterSummaryCard;

use Podlove\DomDocumentFragment;
use Podlove\Model;

class Twitter_Summary_Card extends \Podlove\Modules\Base
{
    protected $module_name = 'Twitter Card Integration';
    protected $module_description = 'Adds Twitter summary card metadata to episodes. <a href="https://cards-dev.twitter.com/validator" target="_blank">Right now, you need to validate and whitelist your site to make it work.</a>';
    protected $module_group = 'web publishing';

    public function load()
    {
        add_action('wp', [$this, 'register_hooks']);

        $this->register_option('site', 'string', [
            'label' => __('Twitter Site', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => __('@username for the website used in the card footer', 'podlove-podcasting-plugin-for-wordpress'),
            'html' => ['class' => 'regular-text podlove-check-input'],
        ]);

        $this->register_option('creator', 'string', [
            'label' => __('Twitter Creator', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => __('@username for the content creator / author', 'podlove-podcasting-plugin-for-wordpress'),
            'html' => ['class' => 'regular-text podlove-check-input'],
        ]);
    }

    /**
     * Register hooks on episode pages only.
     */
    public function register_hooks()
    {
        if (!is_single()) {
            return;
        }

        if ('podcast' !== get_post_type()) {
            return;
        }

        add_action('wp_head', [$this, 'insert_twitter_card_metadata']);
    }

    /**
     * Insert HTML meta tags into site head.
     */
    public function insert_twitter_card_metadata()
    {
        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }

        $episode = \Podlove\Model\Episode::find_one_by_post_id($post_id);
        if (!$episode) {
            return;
        }

        $podcast = Model\Podcast::get();
        $cover_art_url = $episode->cover_art_with_fallback()->url();

        // define meta tags
        $data = [
            [
                'name' => 'twitter:card',
                'content' => 'summary',
            ],
            [
                'name' => 'twitter:url',
                'content' => get_permalink(),
            ],
            [
                'name' => 'twitter:title',
                'content' => get_the_title(),
            ],
            [
                'name' => 'twitter:description',
                'content' => $episode->description(),
            ],
        ];

        if ($site = $this->get_module_option('site')) {
            $data[] = [
                'name' => 'twitter:site',
                'content' => $site,
            ];
        }

        if ($creator = $this->get_module_option('creator')) {
            $data[] = [
                'name' => 'twitter:creator',
                'content' => $creator,
            ];
        }

        if ($cover_art_url) {
            $data[] = [
                'name' => 'twitter:image',
                'content' => $cover_art_url,
            ];
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

        echo $dom;
    }
}
