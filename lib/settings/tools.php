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
		global $wpdb;

		$groupings = [
			'4w' => 24 * 7 * 4,
			'3w' => 24 * 7 * 3,
			'2w' => 24 * 7 * 2,
			'1w' => 24 * 7,
			'6d' => 24 * 6,
			'5d' => 24 * 5,
			'4d' => 24 * 4,
			'3d' => 24 * 3,
			'2d' => 24 * 2,
			'1d' => 24
		];

		foreach (Model\Podcast::get()->episodes() as $episode) {
			
			$max_hsr = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT
					  MAX(hours_since_release)
					FROM ' . Model\DownloadIntentClean::table_name() . ' di
					JOIN ' . Model\MediaFile::table_name() . ' mf ON mf.id = di.media_file_id
					WHERE mf.episode_id = %d',
					$episode->id
				)
			);

			foreach ($groupings as $key => $hours) {
				if ($max_hsr > $hours) {
					$sql = $wpdb->prepare(
						'SELECT
						  COUNT(*)
						FROM ' . Model\DownloadIntentClean::table_name() . ' di
						INNER JOIN ' . Model\MediaFile::table_name() . ' mf ON mf.id = di.media_file_id
						INNER JOIN ' . Model\Episode::table_name() . ' e ON mf.episode_id = e.id
						WHERE e.id = %d AND hours_since_release <= %d',
						$episode->id, $hours
					);
					error_log(print_r($sql, true));
					$downloads = $wpdb->get_var($sql);
					update_post_meta($episode->post_id, '_podlove_downloads_' . $key, $downloads);
					error_log(print_r("update_post_meta($episode->post_id, '_podlove_downloads_' . $key, $downloads);", true));
				}
			}
		}
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

			<h3>Tracking &amp; Analytics</h3>

			<p>
				<a id="recalculate_useragents" href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&action=recalculate_useragents') ?>" class="button button-primary">
					<?php echo __( 'Recalculate User Agents', 'podlove-podcasting-plugin-for-wordpress' ) ?>
				</a>

				<div id="progressbar"><div class="progress-label"></div></div>
			</p>

			<div class="clear"></div>

			<p>
				<a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&action=recalculate_analytics') ?>" class="button button-primary">
					<?php echo __( 'Recalculate Analytics', 'podlove-podcasting-plugin-for-wordpress' ) ?>
				</a>
			</p>

			<div class="clear"></div>

			<p>
				<a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&action=recalculate_downloads_table') ?>" class="button button-primary">
					<?php echo __( 'Recalculate Downloads Table', 'podlove-podcasting-plugin-for-wordpress' ) ?>
				</a>
			</p>

			<div class="clear"></div>

		</div>	
		<?php
	}

}
