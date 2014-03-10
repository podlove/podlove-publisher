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

		

		add_action('podlove_dashboard_meta_boxes', function() {
			add_meta_box(
				\Podlove\Settings\Dashboard::$pagehook . '_feeds',
				__( 'Podcast feeds', 'podlove' ),
				'Podlove\Modules\FeedValidation\Feed_Validation::meta_box',
				\Podlove\Settings\Dashboard::$pagehook,
				'normal'
			);
		});
	}

	public function was_activated( $module_name ) {
		if ( ! wp_next_scheduled( 'podlove_feed_validation' ) )
			wp_schedule_event( time(), 'twicedaily', 'podlove_feed_validation' );
	}

	public function was_deactivated( $module_name ) {
		wp_clear_scheduled_hook( 'podlove_feed_validation' );
	}

	public function meta_box() {
		$feeds = \Podlove\Model\Feed::all();
		?>
		<input id="revalidate_feeds" type="button" class="button button-primary" value="<?php _e( 'Revalidate Feeds', 'podlove' ); ?>">

		<table id="dashboard_feed_info">
			<thead>
				<tr>
					<th><?php _e( 'Name', 'podlove' ); ?></th>
					<th><?php _e( 'Slug', 'podlove' ); ?></th>
					<th><?php _e( 'Last Modification', 'podlove' ); ?></th>
					<th><?php _e( 'Entries', 'podlove' ); ?></th>
					<th><?php echo extension_loaded('zlib') ? __( 'Size (compressed)', 'podlove') : __( 'Size', 'podlove'); ?></th>
					<th><?php _e( 'Latest item', 'podlove'); ?></th>
					<th><?php _e( 'Validation', 'podlove' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					foreach ($feeds as $feed_key => $feed) {

						$feed_request = get_transient( 'podlove_dashboard_feed_source_' . $feed->id );
						if ( false === $feed_request ) {
							$feed_request = $feed->getSource();
							set_transient( 'podlove_dashboard_feed_source_' . $feed->id, 
										  $feed_request,
										  3600*24 );
						}

						$feed_validation = get_transient( 'podlove_dashboard_feed_validation_' . $feed->id );
						if ( false === $feed_validation ) {
							$feed_validation = $feed->getValidationIcon();
							set_transient( 'podlove_dashboard_feed_validation_' . $feed->id, 
										  $feed_validation,
										  3600*24 );
						}							 

						$feed_header = $feed_request['headers'];
						$feed_body = $feed_request['body'];
						$feed_items = $feed->post_ids();

						$number_of_items = count( $feed->post_ids() );
						$last_modification = \Podlove\Modules\FeedValidation\Feed_Validation::relative_time_steps(strtotime( isset($feed_header['last-modified']) ? $feed_header['last-modified'] : 0 ));
						$size = \Podlove\format_bytes(strlen( $feed_body ));

						if (extension_loaded('zlib')) {
							$size .= " (" .  \Podlove\format_bytes(strlen( gzdeflate( $feed_body , 9 ) )) . ")";
						}

						$source  = "<tr>\n";
						$source .= "<td><a href='" . admin_url() . "admin.php?page=podlove_feeds_settings_handle&action=edit&feed=" . $feed->id . "'>" . $feed->name ."</a></td>";
						$source .= "<td class='center'><a href='" . $feed->get_subscribe_url() . "'>" . $feed->slug ."</a></td>";
						$source .= "<td class='center'>" . $last_modification ."</td>";
						$source .= "<td class='center'>" . $number_of_items ."</td>";
						$source .= "<td class='center'>" . $size . "</td>";
						$source .= "<td class='center'><a href=\"" . get_permalink( $feed_items[0] ) . "\">". get_the_title( $feed_items[0] ) ."</a></td>";
						$source .= "<td class='center' data-feed-id='" . $feed->id . "'>" . $feed_validation . "</td>";
						$source .= "</tr>\n";
						echo $source;
					}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Main Cron function call.
	 */
	public function do_validations() {

		set_time_limit( 1800 ); // set max_execution_time to half an hour

		Log::get()->addInfo( 'Begin scheduled feed validation.' );

		$this->renewFeedTransients();

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