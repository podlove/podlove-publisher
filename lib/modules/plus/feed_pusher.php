<?php
namespace Podlove\Modules\Plus;

class FeedPusher
{
    private $module;
    private $api;

    public function __construct($module, $api)
    {
        $this->module = $module;
        $this->api    = $api;
    }

    public function init()
    {
        # push all feeds to PLUS whenever any feed changes
        add_action('podlove_model_change', function ($model) {
            if ($model::name() === "podlove_feed") {
                $this->push_all_feeds();
            }
        });

        # push all feeds to PLUS when the feature is enabled
        add_action('podlove_plus_enable_proxy_changed', function ($new_value) {
            if ($new_value) {
                $this->push_all_feeds();
            }
        });
    }

    public function push_all_feeds()
    {
        $feeds = array_map(function ($feed) {
            return $feed->get_subscribe_url();
        }, \Podlove\Model\Feed::all());

        $this->api->push_feeds($feeds);
    }
}
