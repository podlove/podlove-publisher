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
			/* $capability */ 'administrator',
			/* $menu_slug  */ \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE,
			/* $function   */ array(__CLASS__, 'page')
		);

		$this->init_page_documentation(self::$pagehook);

		add_action('load-' . Dashboard::$pagehook, function () {
			// Adding the meta boxes here, so they can be filtered by the user settings.
			add_action('add_meta_boxes_' . Dashboard::$pagehook, function () {
				add_meta_box(Dashboard::$pagehook . '_about',      __('About', 'podlove'),        '\Podlove\Settings\Dashboard\About::content', Dashboard::$pagehook, 'side');		
				add_meta_box(Dashboard::$pagehook . '_statistics', __('At a glance', 'podlove'),  '\Podlove\Settings\Dashboard\Statistics::content', Dashboard::$pagehook, 'normal');
				add_meta_box(Dashboard::$pagehook . '_news',       __('Podlove News', 'podlove'), '\Podlove\Settings\Dashboard\News::content', Dashboard::$pagehook, 'normal');
				
				do_action('podlove_dashboard_meta_boxes');

				add_meta_box(Dashboard::$pagehook . '_validation', __('Validate Podcast Files', 'podlove'), '\Podlove\Settings\Dashboard\FileValidation::content', Dashboard::$pagehook, 'normal');
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

		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Podlove Dashboard', 'podlove' ); ?></h2>

			<div id="poststuff" class="metabox-holder has-right-sidebar">
				
				<!-- sidebar -->
				<div id="side-info-column" class="inner-sidebar">
					<?php do_action( 'podlove_settings_before_sidebar_boxes' ); ?>
					<?php do_meta_boxes( Dashboard::$pagehook, 'side', NULL ); ?>
					<?php do_action( 'podlove_settings_after_sidebar_boxes' ); ?>
				</div>

				<!-- main -->
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_action( 'podlove_settings_before_main_boxes' ); ?>
						<?php do_meta_boxes( Dashboard::$pagehook, 'normal', NULL ); ?>
						<?php do_meta_boxes( Dashboard::$pagehook, 'additional', NULL ); ?>
						<?php do_action( 'podlove_settings_after_main_boxes' ); ?>						
					</div>
				</div>

				<br class="clear"/>

			</div>

			<!-- Stuff for opening / closing metaboxes -->
			<script type="text/javascript">
			jQuery( document ).ready( function( $ ){
				// close postboxes that should be closed
				$( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
				// postboxes setup
				postboxes.add_postbox_toggles( '<?php echo Dashboard::$pagehook; ?>' );
			} );
			</script>

			<form style='display: none' method='get' action=''>
				<?php
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
				?>
			</form>

		</div>
		<?php
	}
}
