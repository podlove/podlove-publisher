<?php

namespace Podlove\Settings;

class Dashboard
{
    use \Podlove\HasPageDocumentationTrait;

    public static $pagehook;

    public function __construct()
    {
        // use \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE to replace
        // default first item name
        Dashboard::$pagehook = add_submenu_page(
            // $parent_slug
            \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE,
            // $page_title
            __('Dashboard', 'podlove-podcasting-plugin-for-wordpress'),
            // $menu_title
            __('Dashboard', 'podlove-podcasting-plugin-for-wordpress'),
            // $capability
            'podlove_read_dashboard',
            // $menu_slug
            \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE,
            // $function
            [__CLASS__, 'page']
        );

        $this->init_page_documentation(self::$pagehook);

        add_action('load-'.Dashboard::$pagehook, function () {
            // Adding the meta boxes here, so they can be filtered by the user settings.
            add_action('add_meta_boxes_'.Dashboard::$pagehook, function () {
                add_meta_box(Dashboard::$pagehook.'_about', __('About', 'podlove-podcasting-plugin-for-wordpress'), '\Podlove\Settings\Dashboard\About::content', Dashboard::$pagehook, 'side');
                add_meta_box(Dashboard::$pagehook.'_statistics', __('At a glance', 'podlove-podcasting-plugin-for-wordpress'), '\Podlove\Settings\Dashboard\Statistics::content', Dashboard::$pagehook, 'normal');
                add_meta_box(Dashboard::$pagehook.'_news', __('Podlove News', 'podlove-podcasting-plugin-for-wordpress'), '\Podlove\Settings\Dashboard\News::content', Dashboard::$pagehook, 'normal');

                do_action('podlove_dashboard_meta_boxes');

                if (current_user_can('administrator')) {
                    add_meta_box(Dashboard::$pagehook.'_validation', __('Validate Podcast Files', 'podlove-podcasting-plugin-for-wordpress'), '\Podlove\Settings\Dashboard\FileValidation::content', Dashboard::$pagehook, 'normal');
                }
            });
            do_action('add_meta_boxes_'.Dashboard::$pagehook);

            wp_enqueue_script('postbox');
            wp_register_script('cornify-js', \Podlove\PLUGIN_URL.'/js/admin/cornify.js');
            wp_enqueue_script('cornify-js');
        });

        add_action('publish_podcast', function () {
            delete_transient('podlove_dashboard_stats');
        });
    }

    public static function page()
    {
        if (apply_filters('podlove_dashboard_page', false) !== false) {
            return;
        }

        \Podlove\load_template('settings/dashboard/dashboard');
    }
}
