<?php

namespace Podlove\Modules\Plus;

class FeedPusher
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
        add_action('admin_footer', function () {
            if (get_option('podlove_plus_push_feeds')) {
                $this->push_all_feeds();
            }
        });

        // push all feeds to PLUS whenever any feed changes
        add_action('podlove_model_change', function ($model) {
            if (in_array($model::name(), ['podlove_feed'])) {
                update_option('podlove_plus_push_feeds', true);
            }
        });

        // push feeds when podcast changes
        add_action('update_option_podlove_podcast', function () {
            update_option('podlove_plus_push_feeds', true);
        });

        // push all feeds to PLUS when the feature is enabled
        add_action('podlove_plus_enable_proxy_changed', function ($new_value) {
            if ($new_value) {
                update_option('podlove_plus_push_feeds', true);
            }
        });
    }

    public function push_all_feeds()
    {
        delete_option('podlove_plus_push_feeds');

        // podcasts
        $feeds = array_map(function ($feed) {
            return $feed->get_subscribe_url();
        }, \Podlove\Model\Feed::all());

        $this->api->push_feeds($feeds);

        // shows
        if (\Podlove\Modules\Base::is_active('shows')) {
            $shows = \Podlove\Modules\Shows\Model\Show::all();
            foreach ($shows as $show) {
                $feeds = array_map(function ($feed) use ($show) {
                    return $feed->get_subscribe_url('shows', $show->id);
                }, \Podlove\Model\Feed::all());

                $this->api->push_feeds($feeds);
            }
        }
    }
}
