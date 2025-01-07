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
    }

    public function extend_data_js($data)
    {
        if (!isset($data['plus'])) {
            $data['plus'] = [];
        }

        $data['plus']['storage_enabled'] = Podcast::get()->plus_enable_storage;

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
