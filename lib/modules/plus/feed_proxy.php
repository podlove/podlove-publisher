<?php

namespace Podlove\Modules\Plus;

class FeedProxy
{
    private $module;
    private $api;

    public function __construct($module, $api)
    {
        $this->module = $module;
        $this->api = $api;
    }

    public static function is_enabled()
    {
        return (bool) \Podlove\Model\Podcast::get()->plus_enable_proxy;
    }

    public function init()
    {
        add_action('podlove_plus_api_push_feeds', [$this, 'refresh_feed_proxy_cache']);
    }

    public function refresh_feed_proxy_cache()
    {
        $feeds = $this->api->list_feeds();
        update_option('podlove_proxy_feeds', $feeds);
    }

    public static function get_proxy_url($origin_url)
    {
        $feeds = get_option('podlove_proxy_feeds');

        if (!is_array($feeds)) {
            return null;
        }

        return array_reduce($feeds, function ($agg, $item) use ($origin_url) {
            if ($agg !== null) {
                return $agg;
            }

            if (self::normalize_url($item->origin_url) == self::normalize_url($origin_url)) {
                return $item->proxy_url;
            }
        }, null);
    }

    private static function normalize_url($url)
    {
        $url = trim($url);

        return preg_replace('/^https?:\/\//', '', $url);
    }
}
