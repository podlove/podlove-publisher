<?php

namespace Podlove\Modules\Plus;

use Podlove\Model\Podcast;

class FileStorage
{
    private $module;
    private $api;
    private static $nonce = 'update_assets_settings';

    public function __construct($module, $api)
    {
        $this->module = $module;
        $this->api = $api;
    }

    public function init()
    {
        add_action('podlove_before_assign_assets_settings', [$this, 'settings_card']);
        add_action('podlove_data_js', [$this, 'extend_data_js']);

        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'podlove_episode_assets_settings_handle' && isset($_REQUEST['update_plus_settings']) && $_REQUEST['update_plus_settings'] == 'true') {
            add_action('admin_bar_init', [$this, 'save_setting']);
        }

        add_filter('podlove_file_url_template', [self::class, 'file_url_template']);
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

    public static function file_url_template($template)
    {
        if (self::is_enabled()) {
            $base_url = Plus::base_url();
            $podcast = Podcast::get();
            $template = trailingslashit($base_url).'download/'.$podcast->plus_slug.'/%episode_slug%%suffix%.%format_extension%';
        }

        return $template;
    }

    public static function get_local_file_url($file)
    {
        if (self::is_enabled()) {
            // Get local URL by temporarily removing the filter
            remove_filter('podlove_file_url_template', [self::class, 'file_url_template']);
            $local_url = $file->get_file_url();
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

    public function save_setting()
    {
        if (!wp_verify_nonce($_REQUEST['_podlove_nonce'], self::$nonce)) {
            return;
        }

        $podcast_settings = get_option('podlove_podcast', []);

        if (isset($_REQUEST['podlove_podcast'])) {
            $podcast_settings['plus_enable_storage'] = $_REQUEST['podlove_podcast']['plus_enable_storage'] == 'on';
        } else {
            $podcast_settings['plus_enable_storage'] = false;
        }

        update_option('podlove_podcast', $podcast_settings);

        do_action('podlove_plus_enable_storage_changed', $podcast_settings['plus_enable_storage']);

        header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_episode_assets_settings_handle');
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
            $config['description'] = '<strong>' . __('This setting is managed automatically by PLUS File Storage.', 'podlove-podcasting-plugin-for-wordpress') . '</strong>';
        }

        return $config;
    }
}
