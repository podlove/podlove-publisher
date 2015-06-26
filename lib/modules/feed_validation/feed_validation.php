<?php
namespace Podlove\Modules\FeedValidation;
use Podlove\Log;
use Podlove\Model;
use Podlove\Modules\FeedValidation\Model\FeedValidator;

class Feed_Validation extends \Podlove\Modules\Base {

	protected $module_name = 'Feed Validation';
	protected $module_description = 'Automatically validate feeds once in a while or if new posts are published.';
	protected $module_group = 'system';

	public function load() {
		add_action( 'podlove_module_was_activated_feed_validation', array( $this, 'was_activated' ) );
		add_action( 'podlove_module_was_deactivated_feed_validation', array( $this, 'was_deactivated' ) );
		add_action( 'podlove_feed_validation', array( $this, 'do_validations' ) );
		add_action( 'publish_podcast', array( $this, 'set_renewFeedTransients_cron' ) );
		add_action( 'delete_post', array( $this, 'set_renewFeedTransients_cron' ) );
		add_action( 'wp_ajax_podlove-validate-feed', array( $this, 'ajax_validate_feed' ) );
		add_action( 'wp_ajax_podlove-feed-info', array( $this, 'ajax_feed_info' ) );

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

		if (current_user_can('administrator')) {
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
	}

	public function was_activated( $module_name ) {
		if ( ! wp_next_scheduled( 'podlove_feed_validation' ) )
			wp_schedule_event( time(), 'twicedaily', 'podlove_feed_validation' );
	}

	public function was_deactivated( $module_name ) {
		wp_clear_scheduled_hook( 'podlove_feed_validation' );
	}

	/**
	 * Set Cron event for NOW after episode is deleted or published.
	 */

	public function set_renewFeedTransients_cron() {
		wp_schedule_single_event( time(), 'renewFeedTransients' );
	}

	public static function meta_box() {
		$feeds = \Podlove\Model\Feed::all();
		?>
		<input id="revalidate_feeds" type="button" class="button button-primary" value="<?php _e( 'Revalidate Feeds', 'podlove' ); ?>">

		<table id="dashboard_feed_info">
			<thead>
				<tr>
					<th><?php _e( 'Name', 'podlove' ); ?></th>
					<th><?php _e( 'Slug/URL', 'podlove' ); ?></th>
					<th><?php _e( 'Last Modification', 'podlove' ); ?></th>
					<th><?php echo extension_loaded('zlib') ? __( 'Size (compressed)', 'podlove') : __( 'Size', 'podlove'); ?></th>
					<th><?php _e( 'Latest item', 'podlove'); ?></th>
					<th><?php _e( 'Validation', 'podlove' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					foreach ($feeds as $feed_key => $feed) {

						$feed_request    = get_transient( 'podlove_dashboard_feed_information_' . $feed->id );
						$feed_validation = get_transient( 'podlove_dashboard_feed_validation_' . $feed->id );

						$source  = "<tr>\n";
						$source .= "<td><a href='" . admin_url() . "admin.php?page=podlove_feeds_settings_handle&action=edit&feed=" . $feed->id . "'>" . $feed->name ."</a></td>";
						$source .= "<td class='center'><a href='" . $feed->get_subscribe_url() . "'>" . $feed->slug ."</a></td>";
						if ($feed_request === false || $feed_validation === false) {
							$source .= "<td class='center'></td>";
							$source .= "<td class='center'></td>";
							$source .= "<td class='center'></td>";
							$source .= "<td class='center' data-feed-id='" . $feed->id . "' data-feed-redirect='0' data-needs-validation>" . \Podlove\Modules\FeedValidation\Model\FeedValidator::FEED_VALIDATION_INACTIVE . "</td>";
						} else {
							$source .= "<td class='center'>" . $feed_request['last_modification'] ."</td>";
							$source .= "<td class='center'>" . $feed_request['size'] . "</td>";
							$source .= "<td class='center'>" . $feed_request['latest_item'] ."</td>";
							$source .= "<td class='center' data-feed-id='" . $feed->id . "' data-feed-redirect='0'>" . $feed_validation . "</td>";
						}							 
						$source .= "</tr>\n";

						if ( $feed->redirect_http_status == '403' || $feed->redirect_http_status == '307' ) {

							$feed_request_redirected    = get_transient( 'podlove_dashboard_feed_r_information_' . $feed->id );
							$feed_validation_redirected = get_transient( 'podlove_dashboard_feed_r_validation_' . $feed->id );

							$source .= "<tr>\n";
							$source .= "<td></td>";
							$source .= "<td class='center'><a href='" . $feed->redirect_url . "'>" . $feed->slug ."</a></td>";
							if ( false === $feed_request_redirected || $feed_validation_redirected === false ) {
								$source .= "<td class='center'></td>";
								$source .= "<td class='center'></td>";
								$source .= "<td class='center'></td>";
								$source .= "<td class='center' data-feed-id='" . $feed->id . "' data-feed-redirect='1' data-needs-validation>" . \Podlove\Modules\FeedValidation\Model\FeedValidator::FEED_VALIDATION_INACTIVE . "</td>";
							} else {
								$source .= "<td class='center'>" . $feed_request_redirected['last_modification'] ."</td>";
								$source .= "<td class='center'>" . $feed_request_redirected['size'] . "</td>";
								$source .= "<td class='center'>" . $feed_request_redirected['latest_item'] ."</td>";
								$source .= "<td class='center' data-feed-id='" . $feed->id . "' data-feed-redirect='1'>" . $feed_validation_redirected . "</td>";

							}						 
							$source .= "</tr>\n";						

						}

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

			set_transient( 'podlove_dashboard_feed_information_' . $feed->id, 
						  \Podlove\Modules\FeedValidation\Model\FeedValidator::getInformation( $feed->id ),
						  3600*24 );

			set_transient( 'podlove_dashboard_feed_validation_' . $feed->id, 
						  \Podlove\Modules\FeedValidation\Model\FeedValidator::getValidationIcon( $feed->id ),
						  3600*24 );
			
			if ( $feed->redirect_http_status == '403' || $feed->redirect_http_status == '307' ) {

					set_transient( 'podlove_dashboard_feed_r_information_' . $feed->id, 
							  \Podlove\Modules\FeedValidation\Model\FeedValidator::getInformation( $feed->id, TRUE ),
							  3600*24 );

					set_transient( 'podlove_dashboard_feed_r_validation_' . $feed->id, 
							  \Podlove\Modules\FeedValidation\Model\FeedValidator::getValidationIcon( $feed->id, TRUE ),
							  3600*24 );
			}

		}
	}

	public function ajax_feed_info() {
		$feed_id = $_REQUEST['feed_id'];
		$redirect = ( $_REQUEST['redirect'] == '0' ? FALSE : TRUE );

		$feed = \Podlove\Model\Feed::find_by_id( $feed_id );

		\Podlove\AJAX\Ajax::respond_with_json( \Podlove\Modules\FeedValidation\Model\FeedValidator::getInformation( $feed_id, $redirect ) );
	}

	public function ajax_validate_feed() {
		$feed_id = $_REQUEST['feed_id'];
		$redirect = ( $_REQUEST['redirect'] == '0' ? FALSE : TRUE );
	 
	 	$feed = \Podlove\Model\Feed::find_by_id( $feed_id );
	 	// Delete feed source transient
			$errors_and_warnings = \Podlove\Modules\FeedValidation\Model\FeedValidator::getValidationErrorsandWarnings( $feed->id, $redirect );
		// renew transients
	 	set_transient( 'podlove_dashboard_feed_validation_' . $feed->id, 
											  \Podlove\Modules\FeedValidation\Model\FeedValidator::getValidationIcon( $feed->id, $redirect ),
											  3600*24 );
		set_transient( 'podlove_dashboard_feed_information_' . $feed->id,
											  \Podlove\Modules\FeedValidation\Model\FeedValidator::getInformation( $feed->id, $redirect ),
											  3600*24 );

		if ( $redirect === TRUE ) {
			 	set_transient( 'podlove_dashboard_feed_r_validation_' . $feed->id, 
													  \Podlove\Modules\FeedValidation\Model\FeedValidator::getValidationIcon( $feed->id, $redirect ),
													  3600*24 );
				set_transient( 'podlove_dashboard_feed_r_information_' . $feed->id,
													  \Podlove\Modules\FeedValidation\Model\FeedValidator::getInformation( $feed->id, $redirect ),
													  3600*24 );
		}
	 	
	 	\Podlove\AJAX\Ajax::respond_with_json( array( 'validation_icon' => \Podlove\Modules\FeedValidation\Model\FeedValidator::getValidationIcon( $feed->id, $redirect ) ) );
	 }
	
}