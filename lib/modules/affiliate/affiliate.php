<?php

namespace Podlove\Modules\Affiliate;

class Affiliate extends \Podlove\Modules\Base
{
    protected $module_name = 'Affiliate';
    protected $module_description = 'Amazon etc. affiliate link features.';
    protected $module_group = 'system';

    public static function is_core()
    {
        return true;
    }

    // Was activated
    public function was_activated($module_name = 'affiliate')
    {
    }

    public function load()
    {
        add_action('podlove_podcast_settings_tabs', [__CLASS__, 'podcast_settings_tabs']);
    }

    public static function podcast_settings_tabs($tabs)
    {
        $tabs->addTab(new PodcastAffiliateSettingsTab('affiliate', __('Affiliate', 'podlove-podcasting-plugin-for-wordpress')));

        return $tabs;
    }

    public static function get_tracking_id($site)
    {
        return get_option('podlove_affiliate', [])[$site] ?? null;
    }

    public static function apply_amazon_de_affiliate($url)
    {
        $tracking_id = self::get_tracking_id('amazon_de');

        if (!$tracking_id) {
            return;
        }

        return add_query_arg('tag', $tracking_id, $url);
    }

    public static function apply_thomann_de_affiliate($url)
    {
        $tracking_id = self::get_tracking_id('thomann_de');

        if (!$tracking_id) {
            return;
        }

        return add_query_arg('partner_id', $tracking_id, $url);
    }
}
