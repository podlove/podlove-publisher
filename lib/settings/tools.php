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
			case 'recalculate_downloads_table':
				self::recalculate_downloads_table();
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
		podlove_init_user_agent_refresh();
	}

	public static function recalculate_downloads_table() {
		\Podlove\Analytics\DownloadSumsCalculator::calc_download_sums(true);
	}

	public function page() {

		wp_enqueue_script('podlove-tools-useragent', \Podlove\PLUGIN_URL . '/js/admin/tools/useragent.js', ['jquery'], \Podlove\get_plugin_header('Version'));
		wp_enqueue_script('jquery-ui-progressbar');

		?>

  <style>
  .ui-progressbar {
    position: relative;
    margin-left: 225px;
  }
  .progress-label {
    position: absolute;
    left: 50%;
    top: 4px;
    font-weight: bold;
    text-shadow: 1px 1px 0 #fff;
  }

  #recalculate_useragents {
  	float: left;
  }

  </style>

		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Tools', 'podlove-podcasting-plugin-for-wordpress' ); ?></h2>

			<div class="card" style="max-width: 100%">
				
				<h3>Tracking &amp; Analytics</h3>

				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<?php echo __( 'Recalculate User Agents', 'podlove-podcasting-plugin-for-wordpress' ) ?>
							</th>
							<td>
								<a id="recalculate_useragents" href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&action=recalculate_useragents') ?>" class="button">
									<?php echo __( 'Recalculate User Agents', 'podlove-podcasting-plugin-for-wordpress' ) ?>
								</a>

								<div id="progressbar"><div class="progress-label"></div></div>

								<div class="clear"></div>

								<p class="description">
									<?php echo __('Update user agent metadata based on <code>device-detector</code> library.', 'podlove-podcasting-plugin-for-wordpress'); ?>
								</p>

							</td>
						</tr>
						<tr>
							<th>
								<?php echo __( 'Recalculate Analytics', 'podlove-podcasting-plugin-for-wordpress' ) ?>
							</th>
							<td>
								<a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&action=recalculate_analytics') ?>" class="button">
									<?php echo __( 'Recalculate Analytics', 'podlove-podcasting-plugin-for-wordpress' ) ?>
								</a>

								<p class="description">
									<?php echo __('Recalculates contents of <code>podlove_download_intent_clean</code> table based on <code>podlove_download_intent</code> table. Clears cache.', 'podlove-podcasting-plugin-for-wordpress'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th>
								<?php echo __( 'Recalculate Downloads Table', 'podlove-podcasting-plugin-for-wordpress' ) ?>
							</th>
							<td>
								<a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&action=recalculate_downloads_table') ?>" class="button">
									<?php echo __( 'Recalculate Downloads Table', 'podlove-podcasting-plugin-for-wordpress' ) ?>
								</a>

								<p class="description">
									<?php echo __('Recalculates sums for episode downloads in Analytics overview page.', 'podlove-podcasting-plugin-for-wordpress'); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

			</div>


		</div>	
		<?php
	}

}
