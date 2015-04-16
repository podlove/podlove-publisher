<?php
namespace Podlove\Modules\Networks;

use \Podlove\Model\Podcast;

class AdminBarMenu {

	public static function init() {
		add_action( 'admin_bar_menu', [__CLASS__, 'enhance_admin_bar'], 120 );
		add_action( 'admin_head', [__CLASS__, 'print_styles'] );
	}

	public static function print_styles() {
		$podcast_ids = self::podcast_ids();

		$blavatar_classes = implode(", ", array_map(function($id) {
			return "#wp-admin-bar-blog-$id .blavatar";
		}, $podcast_ids));

		$blavatar_before_classes = implode(", ", array_map(function($id) {
			return "#wpadminbar .quicklinks li#wp-admin-bar-blog-$id .blavatar:before";
		}, $podcast_ids));

		$cover_styles = implode("\n", array_map(function($id) {
			return "#wp-admin-bar-blog-$id .blavatar { background-image: url(" . Podcast::get($id)->cover_image . "); }";
		}, $podcast_ids));
		?>
<style type="text/css">
<?php echo $cover_styles; ?>
<?php echo $blavatar_classes ?> {
	background-size: 100% 100%;
	margin-right: 5px;
	width: 18px;
	height: 18px;
	position: relative;
	top: 4px;
	left: -2px;
}

<?php echo $blavatar_before_classes; ?> {
	content: none;
}
</style>
		<?php
	}

	public static function enhance_admin_bar($wp_admin_bar) {
		do_action('podlove_network_admin_bar', $wp_admin_bar);
		self::add_podcast_list($wp_admin_bar);

		// add network dashboard
		$wp_admin_bar->add_node([
			'id'     => self::podcast_toolbar_id('network', 'dashboard'),
			'title'  => __( 'Podlove Dashboard', 'podlove' ),
			'parent' => 'network-admin',
			'href'   => network_admin_url('admin.php?page=podlove_network_settings_handle')
		]);

		// add network templates
		$wp_admin_bar->add_node([
			'id'     => self::podcast_toolbar_id('network', 'templates'),
			'title'  => __( 'Podlove Templates', 'podlove' ),
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
				'title'  => __( 'Podlove Dashboard', 'podlove' ),
				'parent' => 'blog-' . $podcast_id,
				'href'   => get_admin_url($podcast_id, 'admin.php?page=podlove_settings_handle')
			]);

			$wp_admin_bar->add_node([
				'id'     => self::podcast_toolbar_id($podcast_id, 'episodes'),
				'title'  => __( 'Podlove Episodes', 'podlove' ),
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