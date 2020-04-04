<?php
namespace Podlove\Modules\Plus;

/**
 * Global Feed Settings
 *
 * Render and manage settings card on global feeds page.
 */
class GlobalFeedSettings
{
    public function init()
    {
        add_action('podlove_before_feed_global_settings', [$this, 'global_feed_setting']);

        if (isset($_REQUEST["page"]) && $_REQUEST["page"] == "podlove_feeds_settings_handle" && isset($_REQUEST["update_plus_settings"]) && $_REQUEST["update_plus_settings"] == "true") {
            add_action('admin_bar_init', array($this, 'save_global_plus_feed_setting'));
        }
    }

    public function save_global_plus_feed_setting()
    {
        $podcast_settings = get_option('podlove_podcast', []);

        if (isset($_REQUEST['podlove_podcast'])) {
            $podcast_settings['plus_enable_proxy'] = $_REQUEST['podlove_podcast']['plus_enable_proxy'] == "on";
        } else {
            $podcast_settings['plus_enable_proxy'] = false;
        }

        update_option('podlove_podcast', $podcast_settings);

        do_action('podlove_plus_enable_proxy_changed', $podcast_settings['plus_enable_proxy']);

        header('Location: ' . get_site_url() . '/wp-admin/admin.php?page=podlove_feeds_settings_handle');
    }

    public function global_feed_setting()
    {
        ?>
		<div class="podlove-form-card">
		<form method="post" action="admin.php?page=podlove_feeds_settings_handle&amp;update_plus_settings=true">
        <?php

        settings_fields(\Podlove\Settings\Podcast::$pagehook);

        $podcast = \Podlove\Model\Podcast::get();

        $form_attributes = array(
            'context' => 'podlove_podcast',
            'form'    => false,
        );

        \Podlove\Form\build_for($podcast, $form_attributes, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);
            $podcast = $form->object;

            $wrapper->subheader(__('Feed Proxy | Publisher Plus', 'podlove-podcasting-plugin-for-wordpress'));

            $wrapper->checkbox('plus_enable_proxy', [
                'label'       => __('Enable Feed Proxy', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('When Feed Proxy is enabled, all feed requests are automatically redirected to the corresponding proxy feed URL. It can be disabled at any time without risk of losing subscribers because a temporary redirect (HTTP 307) is used.', 'podlove-podcasting-plugin-for-wordpress'),
                'default'     => false,
            ]);
        });
        ?>
		</form>
		</div>
    <?php

    }
}
