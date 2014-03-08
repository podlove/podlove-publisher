<?php
namespace Podlove\Modules\FeedValidation;
use Podlove\Log;
use Podlove\Model;

class Feed_Validation extends \Podlove\Modules\Base {

	protected $module_name = 'Feed Validation';
	protected $module_description = 'Automatically validate feeds once in a while or if new posts are published.';
	protected $module_group = 'system';

	public function load() {
		add_action( 'podlove_module_was_activated_feed_validation', array( $this, 'was_activated' ) );
		add_action( 'podlove_module_was_deactivated_feed_validation', array( $this, 'was_deactivated' ) );
		add_action( 'podlove_feed_validation', array( $this, 'do_validations' ) );
		add_action( 'publish_podcast', array( $this, 'renewFeedTransients' ) );
		add_action( 'delete_post', array( $this, 'renewFeedTransients' ) );
		add_action( 'podlove_module_before_settings_feed_validation', function () {
			if ( $timezone = get_option( 'timezone_string' ) )
				date_default_timezone_set( $timezone );
			?>
			<div>
				<em>
					<?php
					echo sprintf(
						__( 'Next scheduled validation: %s' ),
						date( get_option('date_format') . ' ' . get_option( 'time_format' ), wp_next_scheduled( 'podlove_feed_validation' ) )
					);
					?>
				</em>
			</div>
			<?php
		} );
	}

	public function was_activated( $module_name ) {
		if ( ! wp_next_scheduled( 'podlove_feed_validation' ) )
			wp_schedule_event( time(), 'twicedaily', 'podlove_feed_validation' );
	}

	public function was_deactivated( $module_name ) {
		wp_clear_scheduled_hook( 'podlove_feed_validation' );
	}

	/**
	 * Main Cron function call.
	 */
	public function do_validations() {

		set_time_limit( 1800 ); // set max_execution_time to half an hour

		Log::get()->addInfo( 'Begin scheduled feed validation.' );

		$this->renewTransients();

		Log::get()->addInfo( 'End scheduled feed validation.' );
	}

	/**
	 * Renew Transients
	 */

	public function renewFeedTransients()
	{
		foreach ( \Podlove\Model\Feed::all() as $feed_key => $feed ) {
			// Delete transients
			delete_transient( 'podlove_dashboard_feed_validation_' . $feed->id );
			delete_transient( 'podlove_dashboard_feed_source_' . $feed->id );

			// Performing validation and log the errors
			$errors_and_warnings = $feed->getValidationErrorsandWarnings();
			
			if( $errors_and_warnings )
				$feed->logValidation( $errors_and_warnings );
			// Refresh the transient
			set_transient( 'podlove_dashboard_feed_validation_' . $feed->id, 
											  $feed->getValidationIcon(),
											  3600*24 );
			set_transient( 'podlove_dashboard_feed_source_' . $feed->id, 
											  $feed->getSource(),
											  3600*24 );
		}
	}

	public static function relative_time_steps($time) {
		$time_diff = time() - $time;
		$formated_time_string = date('Y-m-d h:i:s', $time);

		if($time_diff == 0) {
			return 'Now';
		} else {   
			if($time_diff < 60)		return "<span title='" . $formated_time_string . "'>" . __( 'Just now', 'podlove' ) . "</span>";
			if($time_diff < 120)	return "<span title='" . $formated_time_string . "'>" . __( '1 minute ago', 'podlove' ) . "</span>";
			if($time_diff < 3600)	return "<span title='" . $formated_time_string . "'>" . floor($time_diff / 60) . __( ' minutes ago', 'podlove' ) . "</span>";
			if($time_diff < 7200)	return "<span title='" . $formated_time_string . "'>" . __( '1 hour ago', 'podlove' ) . "</span>";
	 		if($time_diff < 86400)	return "<span title='" . $formated_time_string . "'>" . floor($time_diff / 3600) . __( ' hours ago', 'podlove' ) . "</span>";

			return $formated_time_string;      
		}
	}
	
}