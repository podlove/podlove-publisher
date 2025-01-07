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

        add_filter('podlove_file_url_template', [$this, 'file_url_template']);
    }

    // TODO: disable template setting form when enabled
    public function file_url_template($template)
    {
        if (self::is_enabled()) {
            $base_url = Plus::base_url();
            $podcast = Podcast::get();
            $template = $base_url.'/download/'.$podcast->plus_slug.'/%episode_slug%%suffix%.%format_extension%';
        }

        return $template;
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

    // Then:
    // - for the public urls, reverse proxy plus.podlove.org/d/<path> to
    //   whatever the S3 URL is, for example locally
    //   http://minio:9000/podlove-plus/podlovers%2Flov36.mp3
    // - for the reverse proxy to work, Publisher needs to know the podcast
    //   slug. figure out how I communicate that
    // - which reminds me: check when the podcast is created in PLUS (might be
    //   tied to feed proxy logic) and make sure it's done as early as possible
    //   (while hopefully the podcast name is set already and correctly... at
    //   the end of the assistant is a good spot, but it can't be the only one)
    // - adjust media location settings page
    // - adjust url generation centrally
    // - how do I handle CNAMEs?

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

    public function settings_card()
    {
        ?>
		<div class="podlove-form-card">
    <form method="post" action="admin.php?page=podlove_episode_assets_settings_handle&amp;update_plus_settings=true">
    <?php

        settings_fields(\Podlove\Settings\Podcast::$pagehook);

        $form_attributes = [
            'context' => 'podlove_podcast',
            'form' => false,
            'nonce' => self::$nonce
        ];

        \Podlove\Form\build_for(Podcast::get(), $form_attributes, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);

            $wrapper->subheader(__('File Storage | Publisher Plus', 'podlove-podcasting-plugin-for-wordpress'));

            $wrapper->checkbox('plus_enable_storage', [
                'label' => __('Enable Storage', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Put all yo files in da cloud! And serve them from there. Nice man!', 'podlove-podcasting-plugin-for-wordpress'),
                'default' => false,
            ]);
        }); ?>
    </form>
    </div>
    <?php
    }
}
