<?php
namespace Podlove\Settings;

class Dashboard {

	use \Podlove\HasPageDocumentationTrait;

	static $pagehook;

	public function __construct() {

		// use \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE to replace
		// default first item name
		Dashboard::$pagehook = add_submenu_page(
			/* $parent_slug*/ \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE,
			/* $page_title */ __('Dashboard', 'podlove'),
			/* $menu_title */ __('Dashboard', 'podlove'),
			/* $capability */ 'podlove_read_dashboard',
			/* $menu_slug  */ \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE,
			/* $function   */ array(__CLASS__, 'page')
		);

		$roles = ['administrator', 'editor', 'author'];
		foreach ($roles as $role_name) {
			$role = get_role($role_name);
			if (!$role->has_cap('podlove_read_dashboard')) {
				$role->add_cap('podlove_read_dashboard');
			}
		}

		$this->init_page_documentation(self::$pagehook);

		add_action('load-' . Dashboard::$pagehook, function () {
			// Adding the meta boxes here, so they can be filtered by the user settings.
			add_action('add_meta_boxes_' . Dashboard::$pagehook, function () {
				add_meta_box(Dashboard::$pagehook . '_about',      __('About', 'podlove'),        '\Podlove\Settings\Dashboard\About::content', Dashboard::$pagehook, 'side');		
				add_meta_box(Dashboard::$pagehook . '_statistics', __('At a glance', 'podlove'),  '\Podlove\Settings\Dashboard\Statistics::content', Dashboard::$pagehook, 'normal');
				add_meta_box(Dashboard::$pagehook . '_news',       __('Podlove News', 'podlove'), '\Podlove\Settings\Dashboard\News::content', Dashboard::$pagehook, 'normal');
				
				do_action('podlove_dashboard_meta_boxes');

				if (current_user_can('administrator')) {
					add_meta_box(Dashboard::$pagehook . '_validation', __('Validate Podcast Files', 'podlove'), '\Podlove\Settings\Dashboard\FileValidation::content', Dashboard::$pagehook, 'normal');
				}
			});
			do_action('add_meta_boxes_' . Dashboard::$pagehook);

			wp_enqueue_script('postbox');
			wp_register_script('cornify-js', \Podlove\PLUGIN_URL . '/js/admin/cornify.js');
			wp_enqueue_script('cornify-js');
		} );

		add_action( 'publish_podcast', function() {
			delete_transient('podlove_dashboard_stats');
		} );
	}

	public static function page() {

		if (apply_filters('podlove_dashboard_page', false) !== false)
			return;

		\Podlove\load_template('settings/dashboard/dashboard');
	}
}
