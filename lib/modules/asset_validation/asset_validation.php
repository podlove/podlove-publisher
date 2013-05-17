<?php
namespace Podlove\Modules\AssetValidation;
use Podlove\Log;
use Podlove\Model;

class Asset_Validation extends \Podlove\Modules\Base {

	protected $module_name = 'Asset Validation';
	protected $module_description = 'Automatically validate all assets once in a while.';

	public function load() {
		add_action( 'podlove_module_was_activated_asset_validation', array( $this, 'was_activated' ) );
		add_action( 'podlove_module_was_deactivated_asset_validation', array( $this, 'was_deactivated' ) );
		add_action( 'podlove_asset_validation', array( $this, 'do_valiations' ) );
	}

	public function was_activated( $module_name ) {
		if ( ! wp_next_scheduled( 'podlove_asset_validation' ) )
			wp_schedule_event( time(), 'hourly', 'podlove_asset_validation' );
	}

	public function was_deactivated( $module_name ) {
		wp_clear_scheduled_hook( 'podlove_asset_validation' );
	}

	/**
	 * Main Cron function call.
	 */
	public function do_valiations() {

		set_time_limit( 1800 ); // set max_execution_time to half an hour

		Log::get()->addInfo( 'Begin scheduled asset validation.' );

		$new_posts_query = $this->get_new_posts_needing_validation();
		while ( $new_posts_query->have_posts() ) {
			$this->validate_post( $new_posts_query->next_post() );
		}

		$adolescent_posts_query = $this->get_adolescent_posts_needing_validation();
		while ( $adolescent_posts_query->have_posts() ) {
			$this->validate_post( $adolescent_posts_query->next_post() );
		}

		$aged_posts_query = $this->get_aged_posts_needing_validation();
		while ( $aged_posts_query->have_posts() ) {
			$this->validate_post( $aged_posts_query->next_post() );
		}

		Log::get()->addInfo( 'End scheduled asset validation.' );
	}

	private function validate_post( \WP_Post $post ) {
		$episode = Model\Episode::find_or_create_by_post_id( $post->ID );
		Log::get()->addInfo( 'Validate episode', array( 'episode_id' => $episode->id ) );
		$episode->refetch_files();
		update_post_meta( $post->ID, 'last_validated_at', time() );
	}

	/**
	 * Get posts of quite some age needing validation
	 *
	 * - "quite some age" meaning older than 4 weeks
	 * - "needing validation" meaning "not validated within 1 day"
	 * 
	 * @return WP_Query
	 */
	private function get_aged_posts_needing_validation() {

		$age_filter = function ( $where = '' ) {
			$where .= " AND post_date < '" . date( 'Y-m-d', strtotime( '-4 weeks' ) ) . "'";
			return $where;
		};
		 
		add_filter( 'posts_where', $age_filter );
		$query = new \WP_Query( array(
			'post_type' => 'podcast',
			'posts_per_page' => -1,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'last_validated_at',
					'value' => 1, // nonsensical but required
					'compare' => 'NOT EXISTS'
				),
				array(
					'type' => 'NUMERIC',
					'key' => 'last_validated_at',
					'compare' => '<=',
					'value' => strtotime( '-6 hours' )
				)
			)
		) );
		remove_filter( 'posts_where', $age_filter );

		return $query;
	}

	/**
	 * Get posts of intermediate age needing validation
	 *
	 * - "intermediate age" meaning older than 24h but younger than 4 weeks
	 * - "needing validation" meaning "not validated within 6 hours"
	 * 
	 * @return WP_Query
	 */
	private function get_adolescent_posts_needing_validation() {

		$age_filter = function ( $where = '' ) {
			$where .= " AND post_date BETWEEN '" . date( 'Y-m-d', strtotime( '-4 weeks' ) ) . "' AND '" . date( 'Y-m-d', strtotime( '-1 day' ) ) . "'";
			return $where;
		};
		 
		add_filter( 'posts_where', $age_filter );
		$query = new \WP_Query( array(
			'post_type' => 'podcast',
			'posts_per_page' => -1,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'last_validated_at',
					'value' => 1, // nonsensical but required
					'compare' => 'NOT EXISTS'
				),
				array(
					'type' => 'NUMERIC',
					'key' => 'last_validated_at',
					'compare' => '<=',
					'value' => strtotime( '-6 hours' )
				)
			)
		) );
		remove_filter( 'posts_where', $age_filter );

		return $query;
	}

	/**
	 * Get new posts needing validation.
	 *
	 * - "new" meaning "published within last 24 hours"
	 * - "needing validation" meaning "not validated within last hour"
	 * 
	 * @return WP_Query
	 */
	private function get_new_posts_needing_validation() {

		$age_filter = function ( $where = '' ) {
			$where .= " AND post_date > '" . date( 'Y-m-d', strtotime( '-1 day' ) ) . "'";
			return $where;
		};
		 
		add_filter( 'posts_where', $age_filter );
		$query = new \WP_Query( array(
			'post_type' => 'podcast',
			'posts_per_page' => -1,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'last_validated_at',
					'value' => 1, // nonsensical but required
					'compare' => 'NOT EXISTS'
				),
				array(
					'type' => 'NUMERIC',
					'key' => 'last_validated_at',
					'compare' => '<=',
					'value' => strtotime( '-1 hour' )
				)
			)
		) );
		remove_filter( 'posts_where', $age_filter );

		return $query;
	}
}