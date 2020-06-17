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
        # fixme: podcast change is not triggered here because the settings page just sets an option
        add_action('podlove_model_change', function ($model) {
            if (in_array($model::name(), ["podlove_feed"])) {
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
        // podcasts
        $feeds = array_map(function ($feed) {
            return $feed->get_subscribe_url();
        }, \Podlove\Model\Feed::all());

        $this->api->push_feeds($feeds);

        // shows
        // fixme: only when show module is on
        // fimxe(unrelated): trigger feed refresh after push
        // $shows = \Podlove\Modules\Shows\Model\Show::all();
        // foreach ($shows as $show) {
        //     $feeds = array_map(function ($feed) use ($show) {
        //         return $feed->get_subscribe_url("shows", $show->id);
        //     }, \Podlove\Model\Feed::all());

        //     $this->api->push_feeds($feeds);
        // }
    }
}
