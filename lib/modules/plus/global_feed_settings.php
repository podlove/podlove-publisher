<?php

namespace Podlove\Modules\Plus;

/**
 * Global Feed Settings.
 *
 * Render and manage settings card on global feeds page.
 */
class GlobalFeedSettings
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
        add_action('podlove_before_feed_global_settings', [$this, 'global_feed_setting']);
        add_action('podlove_feed_settings_proxy', [$this, 'single_feed_proxy_setting'], 10, 2);
        add_filter('podlove_feed_table_url', [$this, 'podlove_feed_table_url'], 10, 2);

        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'podlove_feeds_settings_handle' && isset($_REQUEST['update_plus_settings']) && $_REQUEST['update_plus_settings'] == 'true') {
            add_action('admin_bar_init', [$this, 'save_global_plus_feed_setting']);
        }
    }

    public function save_global_plus_feed_setting()
    {
        $podcast_settings = get_option('podlove_podcast', []);

        if (isset($_REQUEST['podlove_podcast'])) {
            $podcast_settings['plus_enable_proxy'] = $_REQUEST['podlove_podcast']['plus_enable_proxy'] == 'on';
        } else {
            $podcast_settings['plus_enable_proxy'] = false;
        }

        update_option('podlove_podcast', $podcast_settings);

        do_action('podlove_plus_enable_proxy_changed', $podcast_settings['plus_enable_proxy']);

        header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_feeds_settings_handle');
    }

    public function podlove_feed_table_url($link, $feed)
    {
        $proxy_url = FeedProxy::get_proxy_url($feed->get_subscribe_url());
        $link .= '<br><span title="redirects to">&#8618;</span>&nbsp;';
        if ($proxy_url) {
            $link .= "<a target=\"_blank\" href=\"{$proxy_url}\">{$proxy_url}</a>";
        } else {
            $link .= 'error: unknown redirect URL';
        }

        return $link;
    }

    public function single_feed_proxy_setting($wrapper, $feed)
    {
        $proxy_url = FeedProxy::get_proxy_url($feed->get_subscribe_url());

        $wrapper->callback('plus_redirect_info', [
            'label' => __('PLUS Proxy', 'podlove-podcasting-plugin-for-wordpress'),
            'callback' => function () use ($proxy_url) {
                echo '<p>';
                echo '<a target="_blank" href="'.esc_attr($proxy_url).'">'.$proxy_url.'</a>';
                echo '</p>';
                echo '<p class="description">';
                echo __('You are using Publisher PLUS, which automatically configures the proxy settings for you.', 'podlove-podcasting-plugin-for-wordpress');
                echo '</p>';
            },
        ]);
    }

    public function global_feed_setting()
    {
        ?>
		<div class="podlove-form-card">
		<form method="post" action="admin.php?page=podlove_feeds_settings_handle&amp;update_plus_settings=true">
        <?php

        settings_fields(\Podlove\Settings\Podcast::$pagehook);

        $podcast = \Podlove\Model\Podcast::get();

        $form_attributes = [
            'context' => 'podlove_podcast',
            'form' => false,
        ];

        \Podlove\Form\build_for($podcast, $form_attributes, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);
            $podcast = $form->object;

            $wrapper->subheader(__('Feed Proxy | Publisher Plus', 'podlove-podcasting-plugin-for-wordpress'));

            $wrapper->checkbox('plus_enable_proxy', [
                'label' => __('Enable Feed Proxy', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('When Feed Proxy is enabled, all feed requests are automatically redirected to the corresponding proxy feed URL. It can be disabled at any time without risk of losing subscribers because a temporary redirect (HTTP 307) is used.', 'podlove-podcasting-plugin-for-wordpress'),
                'default' => false,
            ]);
        }); ?>
		</form>
		</div>
    <?php
    }
}
