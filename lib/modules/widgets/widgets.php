<?php

namespace Podlove\Modules\Widgets;

class Widgets extends \Podlove\Modules\Base
{
    protected $module_group = 'web publishing';

    public function get_module_name()
    {
        return __('Widgets', 'podlove-podcasting-plugin-for-wordpress');
    }

    public function get_module_description()
    {
        return __('Brings a bunch of useful Podlove Publisher widgets to WordPress.', 'podlove-podcasting-plugin-for-wordpress');
    }

    public static function is_core()
    {
        return true;
    }

    public function load()
    {
        $widgets = [
            '\Podlove\Modules\Widgets\Widgets\PodcastLicense',
            '\Podlove\Modules\Widgets\Widgets\RecentEpisodes',
            '\Podlove\Modules\Widgets\Widgets\PodcastInformation',
            '\Podlove\Modules\Widgets\Widgets\RenderTemplate',
        ];
        $widgets = apply_filters('podlove_widgets', $widgets);

        foreach ($widgets as $widget_class) {
            add_action('widgets_init', function () use ($widget_class) {
                register_widget($widget_class);
            });
        }
    }
}
