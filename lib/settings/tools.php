<?php
namespace Podlove\Settings;
use \Podlove\Model;
use \Podlove\Cache\TemplateCache;
use \Podlove\Analytics\DownloadIntentCleanup;

class Tools {

	use \Podlove\HasPageDocumentationTrait;

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Tools::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Tools',
			/* $menu_title */ 'Tools',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_tools_settings_handle',
			/* $function   */ array( $this, 'page' )
		);

		$this->init_page_documentation(self::$pagehook);

		add_action( 'admin_init', array( $this, 'process_actions' ) );
	}

	function process_actions() {

		if (filter_input(INPUT_GET, 'page') != 'podlove_tools_settings_handle')
			return;

		switch (filter_input(INPUT_GET, 'action')) {
			case 'recalculate_analytics':
				self::recalculate_analytics();
				break;
			case 'recalculate_useragents':
				self::recalculate_useragents();
				break;
			default:
				# code...
				break;
		}

	}

	public static function recalculate_analytics() {
		Model\DownloadIntentClean::delete_all();
		DownloadIntentCleanup::cleanup_download_intents();
		TemplateCache::get_instance()->setup_purge();
	}

	public static function recalculate_useragents() {
		error_log(print_r("recalc user agents", true));
		podlove_init_user_agent_refresh();
	}

	public function page() {
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Tools', 'podlove-podcasting-plugin-for-wordpress' ); ?></h2>

			<h3>Tracking &amp; Analytics</h3>

			<p>
				<a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&action=recalculate_useragents') ?>" class="button button-primary">
					<?php echo __( 'Recalculate User Agents', 'podlove-podcasting-plugin-for-wordpress' ) ?>
				</a>
			</p>

			<p>
				<a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&action=recalculate_analytics') ?>" class="button button-primary">
					<?php echo __( 'Recalculate Analytics', 'podlove-podcasting-plugin-for-wordpress' ) ?>
				</a>
			</p>

		</div>	
		<?php
	}

}
