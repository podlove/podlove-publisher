<?php 
namespace Podlove\Modules\Logging;

use Monolog\Logger;

use Podlove\Model;
use Podlove\Log;
use Podlove\Settings\Dashboard;

class Logging extends \Podlove\Modules\Base {

	protected $module_name = 'Logging';
	protected $module_description = 'View podlove related logs in dashboard. (writes logs to database)';

	public function load() {
		add_action( 'podlove_module_was_activated_logging', array( $this, 'was_activated' ) );
		add_action( 'init', array( $this, 'register_database_logger' ));

		add_action( 'podlove_dashboard_meta_boxes', array( $this, 'register_meta_box' ) );
	}

	public function was_activated( $module_name ) {
		LogTable::build();
	}

	public function register_database_logger() {
		global $wpdb;

		$log = Log::get();
		$log->pushHandler( new WPDBHandler( $wpdb, $log->get_log_level() ) );
		$log->pushHandler( new WPMailHandler( get_option( 'admin_email' ), "Podlove | Critical notice for " . get_option( 'blogname' ), Logger::CRITICAL ) );
	}

	public function register_meta_box() {
		add_meta_box( Dashboard::$pagehook . '_logging', __( 'Logging', 'podlove' ), array( $this, 'dashoard_template' ), Dashboard::$pagehook, 'normal' );
	}

	public function dashoard_template() {
		?>
		<style type="text/css">
		#podlove-log {
			height: 500px;
			overflow: auto;
			font-family: monospace;
			font-size: 14px;
			line-height: 18px;
			padding: 5px;
		}

		.log-level-200 {  }
		.log-level-400 { color: #95002B; }
		.log-level-550 { background: #95002B; color: #FAD4AF; }
		.log-level-550 a { color: #F4E6AD; }
		</style>

		<script type="text/javascript">
		jQuery(function($) {
			$(document).ready(function() {
				$("#podlove-log").scrollTop(1000000);
			});
		});
		</script>
		
		<div id="podlove-log">
		<?php foreach ( LogTable::all() as $log_entry ): ?>
			<div class="log-entry log-level-<?php echo $log_entry->level ?>">
				<span class="log-date">
					[<?php echo date( 'Y-m-d H:i:s', $log_entry->time ); ?>]
				</span>
				<span class="log-message">
					<?php echo $log_entry->message; ?>
				</span>
				<span class="log-extra">
					<?php
					$data = json_decode( $log_entry->context );
					if ( isset( $data->media_file_id ) ) {
						$media_file = Model\MediaFile::find_by_id( $data->media_file_id );
						$episode = $media_file->episode();
						$asset = $media_file->episode_asset();
						echo sprintf( '<a href="%s">%s / %s</a>', get_edit_post_link( $episode->post_id ), $episode->slug, $asset->title );
					}
					if ( isset( $data->episode_id ) ) {
						$episode = Model\Episode::find_by_id( $data->episode_id );
						echo sprintf( '<a href="%s">%s</a>', get_edit_post_link( $episode->post_id ), get_the_title( $episode->post_id ) );
					}
					?>
				</span>
			</div>
		<?php endforeach; ?>
		</div>
		<?php
	}

}