<?php

namespace Podlove\Modules\Plus;

use Podlove\Model\Podcast;

class FileStorage
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
        add_action('podlove_before_assign_assets_settings', [$this, 'settings_card']);
        add_action('podlove_data_js', [$this, 'extend_data_js']);

        add_filter('podlove_file_url_template', [self::class, 'file_url_template']);
        add_filter('podlove_media_file_base_uri', [self::class, 'file_url_base']);
        add_filter('podlove_url_template_field_config', [$this, 'modify_url_template_field']);

        if (self::is_enabled()) {
            add_filter('podlove_podcast_settings_tabs', [$this, 'remove_media_tab']);
        }
    }

    // the upload location is set automatically when PLUS is enabled
    public function remove_media_tab($tabs)
    {
        $tabs->removeTab('media');

        return $tabs;
    }

    public static function file_url_base($url_base = null)
    {
        if (self::is_enabled()) {
            $base_url = Plus::base_url();
            $podcast = Podcast::get();
            $url_base = trailingslashit($base_url).'download/'.$podcast->plus_slug.'/';
        }

        return $url_base;
    }

    public static function file_url_template($template)
    {
        if (self::is_enabled()) {
            $template = trailingslashit(self::file_url_base()).'%episode_slug%%suffix%.%format_extension%';
        }

        return $template;
    }

    public static function get_local_file_url($file)
    {
        if (self::is_enabled()) {
            // Get local URL by temporarily removing the filter
            remove_filter('podlove_media_file_base_uri', [self::class, 'file_url_base']);
            remove_filter('podlove_file_url_template', [self::class, 'file_url_template']);
            $local_url = $file->get_file_url();
            add_filter('podlove_media_file_base_uri', [self::class, 'file_url_base']);
            add_filter('podlove_file_url_template', [self::class, 'file_url_template']);

            return $local_url;
        }

        return $file->get_file_url();
    }

    public static function is_enabled()
    {
        return Podcast::get()->plus_enable_storage;
    }

    public function extend_data_js($data)
    {
        if (!isset($data['plus'])) {
            $data['plus'] = [];
        }

        $data['plus']['storage_enabled'] = self::is_enabled();

        return $data;
    }

    // advertise the file storage if it is not enabled
    public function settings_card()
    {
        $podcast = \Podlove\Model\Podcast::get();

        if (!$podcast->plus_enable_storage) {
            Banner::file_storage();
        }
    }

    public function modify_url_template_field($config)
    {
        if (self::is_enabled()) {
            $config['attributes'] = 'class="large-text" readonly disabled style="background-color: #f0f0f0; color: #666;"';
            $config['description'] = '<strong>'.__('This setting is managed automatically by PLUS File Storage.', 'podlove-podcasting-plugin-for-wordpress').'</strong>';
        }

        return $config;
    }
}
