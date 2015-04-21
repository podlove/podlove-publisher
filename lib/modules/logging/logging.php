<?php 
namespace Podlove\Modules\Logging;

use Monolog\Logger;

use Podlove\Model;
use Podlove\Log;
use Podlove\Settings\Dashboard;

class Logging extends \Podlove\Modules\Base {

	protected $module_name = 'Logging';
	protected $module_description = 'View podlove related logs in dashboard. (writes logs to database)';
	protected $module_group = 'system';

	public function load() {
		add_action( 'podlove_module_was_activated_logging', array( $this, 'was_activated' ) );
		add_action( 'init', array( $this, 'register_database_logger' ));

		add_action( 'podlove_dashboard_meta_boxes', array( $this, 'register_meta_box' ) );

		self::schedule_crons();
		add_action('podlove_cleanup_logging_table', array(__CLASS__, 'cleanup_logging_table'));
	}

	public static function schedule_crons() {
		if (!wp_next_scheduled('podlove_cleanup_logging_table'))
			wp_schedule_event(time(), 'daily', 'podlove_cleanup_logging_table');
	}

	public static function cleanup_logging_table() {
		LogTable::cleanup();
	}

	public function was_activated( $module_name ) {
		LogTable::build();
	}

	public function register_database_logger() {
		global $wpdb;

		$log = Log::get();
		// write logs to database
		$log->pushHandler( new WPDBHandler( $wpdb, $log->get_log_level() ) );
		// send critical logs via email
		// $log->pushHandler( new WPMailHandler( get_option( 'admin_email' ), "Podlove | Critical notice for " . get_option( 'blogname' ), Logger::CRITICAL ) );
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
				// scroll down
				$("#podlove-log").scrollTop($("#podlove-log")[0].scrollHeight);
			});
		});
		</script>

		<?php
		if ( $timezone = get_option( 'timezone_string' ) )
			date_default_timezone_set( $timezone );
		?>

		<div id="podlove-log">
		<?php foreach ( LogTable::find_all_by_where( "time > " . strtotime("-1 week") ) as $log_entry ): ?>
			<div class="log-entry log-level-<?php echo $log_entry->level ?>">
				<span class="log-date">
					[<?php echo date( 'Y-m-d H:i:s', $log_entry->time ) ?>]
				</span>
				<span class="log-message">
					<?php echo $log_entry->message; ?>
				</span>
				<span class="log-extra">
					<?php
					$data = json_decode( $log_entry->context );
					if ( isset( $data->media_file_id ) ) {
						if ( $media_file = Model\MediaFile::find_by_id( $data->media_file_id ) ) {
							if ( $episode = $media_file->episode() ) {
								if ( $asset = $media_file->episode_asset() ) {
									echo sprintf( '<a href="%s">%s/%s</a>', get_edit_post_link( $episode->post_id ), $episode->slug, $asset->title );
								}
							}
						}
					}
					if ( isset( $data->error ) ) {
						echo sprintf(' "%s"', $data->error);
					}
					if ( isset( $data->episode_id ) ) {
						if ( $episode = Model\Episode::find_by_id( $data->episode_id ) )
							echo sprintf( ' <a href="%s">%s</a>', get_edit_post_link( $episode->post_id ), get_the_title( $episode->post_id ) );
					}
					if ( isset( $data->http_code ) ) {
						echo " HTTP Status: " . $data->http_code;
					}
					if ( isset( $data->mime_type ) && isset( $data->expected_mime_type ) ) {
						echo " Expected: {$data->expected_mime_type}, but found: {$data->mime_type}";
					}
					?>
				</span>
			</div>
		<?php endforeach; ?>
		</div>
		<?php
	}

}