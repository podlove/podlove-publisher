<?php

namespace Podlove\Modules\Plus;

/**
 * Global Feed Settings.
 *
 * Render and manage settings card on global feeds page.
 */
class GlobalFeedSettings
{
    private $module;
    private $api;

    public function __construct($module, $api)
    {
        $this->module = $module;
        $this->api = $api;
    }

    public function init()
    {
        add_action('podlove_before_feed_global_settings', [$this, 'global_feed_setting']);
        add_action('podlove_feed_settings_proxy', [$this, 'single_feed_proxy_setting'], 10, 2);
        add_filter('podlove_feed_table_url', [$this, 'podlove_feed_table_url'], 10, 2);
    }

    public function podlove_feed_table_url($link, $feed)
    {
        $proxy_url = FeedProxy::get_proxy_url($feed->get_subscribe_url());
        $link .= '<br><span title="redirects to">&#8618;</span>&nbsp;';
        if ($proxy_url) {
            $link .= "<a target=\"_blank\" href=\"{$proxy_url}\">{$proxy_url}</a>";
        } else {
            $link .= 'error: unknown redirect URL';
        }

        return $link;
    }

    public function single_feed_proxy_setting($wrapper, $feed)
    {
        $proxy_url = FeedProxy::get_proxy_url($feed->get_subscribe_url());

        $wrapper->callback('plus_redirect_info', [
            'label' => __('PLUS Proxy', 'podlove-podcasting-plugin-for-wordpress'),
            'callback' => function () use ($proxy_url) {
                echo '<p>';
                echo '<a target="_blank" href="'.esc_attr($proxy_url).'">'.$proxy_url.'</a>';
                echo '</p>';
                echo '<p class="description">';
                echo __('You are using Publisher PLUS, which automatically configures the proxy settings for you.', 'podlove-podcasting-plugin-for-wordpress');
                echo '</p>';
            },
        ]);
    }

    // advertise the feed proxy if it is not enabled
    public function global_feed_setting()
    {
        $podcast = \Podlove\Model\Podcast::get();

        if (!$podcast->plus_enable_proxy) {
            Banner::feed_proxy();
        }
    }
}
