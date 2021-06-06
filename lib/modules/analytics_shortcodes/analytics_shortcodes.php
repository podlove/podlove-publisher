<?php

namespace Podlove\Modules\AnalyticsShortcodes;

use Podlove\Model;

class Analytics_Shortcodes extends \Podlove\Modules\Base
{
    protected $module_name = 'Analytics Shortcodes';
    protected $module_description = 'Adds Shortcodes to publish analytics on blog page';
    protected $module_group = 'web publishing';

    public function load()
    {
        add_shortcode('podlove-episode-analytics', [$this, 'podlove_episode_analytics']);
        add_shortcode('podlove-global-analytics', [$this, 'podlove_global_analytics']);
    }

    public function podlove_episode_analytics($atts) {
        $a = shortcode_atts(array(
            'type' => 'total',
            'post' => null
        ), $atts);
    
        $type = $a['type'];
        if(!$this->validate_time_type($type)) {
            return ''; // Unknown time slot
        }
    
        $post = get_post($a['post']);
        if(!$post) {
            return ''; // No post
        }
    
        $total = get_post_meta($post->ID, '_podlove_downloads_' . $type, true);
        if(!$total) {
            // TODO find better solution. May be something with $content and don't render it at all
            return __('unknown', 'podlove-podcasting-plugin-for-wordpress');
        }
        return $total;
    }
    
    public function podlove_global_analytics($atts) {
        $a = shortcode_atts(array(
            'type' => 'total',
        ), $atts);
    
        $type = $a['type'];
    
        switch ($type) {
            case 'total':
                return Model\DownloadIntentClean::total_downloads();
            case 'prev_month_downloads':
                return Model\DownloadIntentClean::prev_month_downloads()['downloads'];
            case 'prev_month_name':
                return Model\DownloadIntentClean::prev_month_downloads()['homan_readable_month'];
            case '7d':
                return Model\DownloadIntentClean::last_7days_downloads();
            case '24h':
                return Model\DownloadIntentClean::last_24hours_downloads();
        }
    
        return '';
    }
    
    public function validate_time_type($type) {
        $valid = array(
            'total', '3y', '2y', '1y', '3q', '2q', '1q', '4w', '3w', '2w', '1w', '6d', '5d', '4d', '3d', '2d', '1d'
        );
        return in_array($type, $valid);
    }

}
