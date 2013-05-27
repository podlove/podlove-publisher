<?php 
namespace Podlove\Modules\Logging;
use \Podlove\Model;
use \Podlove\Log;
use \Podlove\Settings\Dashboard;

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
	}

	public function register_meta_box() {
		add_meta_box( Dashboard::$pagehook . '_logging', __( 'Logging', 'podlove' ), array( $this, 'dashoard_template' ), Dashboard::$pagehook, 'normal' );
	}

	public function dashoard_template() {
		?>
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
						echo sprintf( '<a href="%s">%s</a>', get_edit_post_link( $episode->post_id ), $episode->slug );
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
		.log-level-400 { color: #FF0000; }
		</style>

		<script type="text/javascript">
		jQuery(function($) {
			$(document).ready(function() {
				$("#podlove-log").scrollTop(1000000);
			});
		});
		</script>
		<?php
	}

}