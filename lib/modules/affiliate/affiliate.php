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
}
