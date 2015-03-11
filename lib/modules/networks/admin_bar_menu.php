<?php
namespace Podlove\Modules\Networks;

use \Podlove\Model\Podcast;

class AdminBarMenu {

	public static function init() {
		add_action( 'admin_bar_menu', [__CLASS__, 'enhance_admin_bar'], 120 );
	}

	public static function enhance_admin_bar($wp_admin_bar) {
		self::add_dashbard($wp_admin_bar);

		do_action('podlove_network_admin_bar', $wp_admin_bar);

		self::add_podcast_list($wp_admin_bar);
	}

	private static function add_dashbard($wp_admin_bar) {
		$wp_admin_bar->add_node([
			'id'     => 'podlove_toolbar_dashboard',
			'title'  => __('Dashboard', 'podlove'),
			'parent' => 'podlove_toolbar',
			'href'   => get_admin_url('admin.php?page=podlove_settings_handle'),
			'meta'   => ['class' => 'podlove-toolbar-without-icon']
		]);
	}

	private static function add_podcast_list($wp_admin_bar) {
		foreach (self::podcast_ids() as $podcast_id) {
			self::add_podcast($podcast_id, $wp_admin_bar);
		}
	}

	private static function add_podcast($podcast_id, $wp_admin_bar) {
			switch_to_blog($podcast_id);

			$wp_admin_bar->add_node([
				'id'     => self::podcast_toolbar_id($podcast_id),
				'title'  => get_bloginfo( 'name' ),
				'parent' => 'podlove_toolbar',
				'href'   => get_admin_url($podcast_id, 'admin.php?page=podlove_settings_handle'),
				'meta'   => [
					'class' => 'podlove-toolbar-podcast podlove-toolbar-podcast-' . $podcast_id,
					'html'  => '<img class="podlove-toolbar-podcast-cover" src="' . Podcast::get()->cover_image . '" alt="' . get_bloginfo( 'name' ) . '" />'
				]
			]);

			// Register Dashboard & Episodes per Podcast
			$wp_admin_bar->add_node([
				'id'     => self::podcast_toolbar_id($podcast_id, 'dashboard'),
				'title'  => __( 'Dashboard', 'podlove' ),
				'parent' => self::podcast_toolbar_id($podcast_id),
				'href'   => get_admin_url($podcast_id, 'admin.php?page=podlove_settings_handle'),
				'meta'   => ['class' => 'podlove-toolbar-without-icon']
			]);

			$wp_admin_bar->add_node([
				'id'     => self::podcast_toolbar_id($podcast_id, 'episodes'),
				'title'  => __( 'Episodes', 'podlove' ),
				'parent' => self::podcast_toolbar_id($podcast_id),
				'href'   => get_admin_url($podcast_id, 'edit.php?post_type=podcast'),
				'meta'   => ['class' => 'podlove-toolbar-without-icon']
			]);

			do_action('podlove_network_admin_bar_podcast', $wp_admin_bar, $podcast_id);

			restore_current_blog();
	}

	private static function podcast_ids() {
		return Model\PodcastList::with_network_scope(function() {
			return Model\PodcastList::get_all_podcast_ids();
		});
	}

	private static function podcast_toolbar_id($podcast_id, $suffix = '') {
		return 'podlove_toolbar_' . $podcast_id . ($suffix ? '_' . $suffix : '');
	}
}