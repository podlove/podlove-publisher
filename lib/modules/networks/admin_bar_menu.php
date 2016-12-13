<?php
namespace Podlove\Modules\Networks;

use \Podlove\Model\Podcast;

class AdminBarMenu {

	public static function init() {
		add_action( 'admin_bar_menu', [__CLASS__, 'enhance_admin_bar'], 120 );

		add_action('wp_enqueue_scripts', [__CLASS__, 'maybe_print_styles']);
		add_action('admin_enqueue_scripts', [__CLASS__, 'maybe_print_styles']);
		
		add_action('wp_ajax_podlove-network-cover-style', [__CLASS__, 'print_styles']);
	}

	public static function maybe_print_styles() {
		if (is_admin_bar_showing()) {
			wp_enqueue_style(
				'podlove-network-cover-style',
				admin_url('admin-ajax.php').'?action=podlove-network-cover-style',
				[], \Podlove\get_plugin_header('Version')
			);
		}
	}

	public static function print_styles() {
		header('Content-type: text/css');
		echo \Podlove\Cache\TemplateCache::get_instance()->cache_for("podlove_admin_menu_style_covers", function() {
			return self::get_styles();
		}, HOUR_IN_SECONDS);
		exit;
	}

	private static function get_styles() {
		ob_start();

		$podcast_ids = self::podcast_ids();
		$podcast_ids = array_filter($podcast_ids, function($id) {
			return Podcast::get($id)->has_cover_art();
		});

		$blavatar_classes = implode(", ", array_map(function($id) {
			return "#wp-admin-bar-blog-$id .blavatar";
		}, $podcast_ids));

		$blavatar_before_classes = implode(", ", array_map(function($id) {
			return "#wpadminbar .quicklinks li#wp-admin-bar-blog-$id .blavatar:before";
		}, $podcast_ids));

		$cover_styles = implode("\n", array_map(function($id) {
			return "#wp-admin-bar-blog-$id .blavatar { background-image: url(" . Podcast::get($id)->cover_art()->setWidth(40)->url() . "); }";
		}, $podcast_ids));
		?>
<?php echo $cover_styles; ?>
<?php echo $blavatar_classes ?> {
	background-size: 100% 100%;
	margin-right: 5px;
	width: 20px;
	height: 20px;
	position: relative;
	top: 4px;
	left: -3px;
}

<?php echo $blavatar_before_classes; ?> {
	content: none;
}
		<?php

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	public static function enhance_admin_bar($wp_admin_bar) {
		self::add_podcast_list($wp_admin_bar);
		self::add_network_entries($wp_admin_bar);
	}

	private static function add_network_entries($wp_admin_bar) {
		// add network dashboard
		$wp_admin_bar->add_node([
			'id'     => self::podcast_toolbar_id('network', 'dashboard'),
			'title'  => __( 'Podlove Dashboard', 'podlove-podcasting-plugin-for-wordpress' ),
			'parent' => 'network-admin',
			'href'   => network_admin_url('admin.php?page=podlove_network_settings_handle')
		]);

		// add network templates
		$wp_admin_bar->add_node([
			'id'     => self::podcast_toolbar_id('network', 'templates'),
			'title'  => __( 'Podlove Templates', 'podlove-podcasting-plugin-for-wordpress' ),
			'parent' => 'network-admin',
			'href'   => network_admin_url('admin.php?page=podlove_templates_settings_handle')
		]);
	}

	private static function add_podcast_list($wp_admin_bar) {
		foreach (self::podcast_ids() as $podcast_id) {
			self::add_podcast($podcast_id, $wp_admin_bar);
		}
	}

	private static function add_podcast($podcast_id, $wp_admin_bar) {
			switch_to_blog($podcast_id);

			// Register Dashboard & Episodes per Podcast
			$wp_admin_bar->add_node([
				'id'     => self::podcast_toolbar_id($podcast_id, 'dashboard'),
				'title'  => __( 'Podlove Dashboard', 'podlove-podcasting-plugin-for-wordpress' ),
				'parent' => 'blog-' . $podcast_id,
				'href'   => get_admin_url($podcast_id, 'admin.php?page=podlove_settings_handle')
			]);

			$wp_admin_bar->add_node([
				'id'     => self::podcast_toolbar_id($podcast_id, 'episodes'),
				'title'  => __( 'Podlove Episodes', 'podlove-podcasting-plugin-for-wordpress' ),
				'parent' => 'blog-' . $podcast_id,
				'href'   => get_admin_url($podcast_id, 'edit.php?post_type=podcast')
			]);

			do_action('podlove_network_admin_bar_podcast', $wp_admin_bar, $podcast_id);

			restore_current_blog();
	}

	private static function podcast_ids() {
		return Model\PodcastList::with_network_scope(function() {
			return Model\Network::podcast_blog_ids();
		});
	}

	private static function podcast_toolbar_id($podcast_id, $suffix = '') {
		return 'podlove_toolbar_' . $podcast_id . ($suffix ? '_' . $suffix : '');
	}
}
