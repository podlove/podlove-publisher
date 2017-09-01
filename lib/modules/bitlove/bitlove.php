<?php
namespace Podlove\Modules\Bitlove;
use \Podlove\Model;

class Bitlove extends \Podlove\Modules\Base {

	protected $module_name = 'Bitlove';
	protected $module_description = 'Enable support for <a href="http://bitlove.org/" target="_blank">Bitlove</a>. Bitlove creates Torrents for all enclosures of an RSS/ATOM feed and seeds them. Unfortunately, there is only limited support if you use a feedproxy like Feedburner or feedpress.it.';
	protected $module_group = 'external services';

	public function load() {

		add_action( 'podlove_feeds_directories', array( $this, 'enable_bitlove_flag_for_feed' ) );

		add_action( 'podlove_module_was_activated_bitlove', array( $this, 'was_activated' ) );
		add_action( 'podlove_module_was_deactivated_bitlove', array( $this, 'was_deactivated' ) );

		add_action( 'admin_init', array( $this, 'add_feed_model_extension' ) );

		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );

		add_action( 'wp_ajax_podlove-fetch-bitlove-url', array( $this, 'fetch_bitlove_url' ) );

		RssExtension::init();
	}

	public function fetch_bitlove_url() {
		\Podlove\AJAX\Ajax::respond_with_json( array(
			'bitlove_url' => self::get_bitlove_feed_url( $_REQUEST['feed_id'] )
		) );
	}

	public function admin_print_styles() {

		if (!isset($_REQUEST['page']) || $_REQUEST['page'] !== 'podlove_feeds_settings_handle')
			return;

		wp_register_script(
			'podlove_bitlove_admin_script',
			$this->get_module_url() . '/js/admin.js',
			array( 'jquery', 'jquery-ui-tabs' ),
			\Podlove\get_plugin_header( 'Version' )
		);
		wp_enqueue_script('podlove_bitlove_admin_script');
	}

	public static function get_bitlove_feed_url( $feed_id ) {
		$feed = \Podlove\Model\Feed::find_one_by_id( $feed_id );
		$cache_key = "podlove_bitlove_feed_url_" . $feed_id;

		if ( ( $bitlove_feed_url = get_transient( $cache_key ) ) !== FALSE ) {
			return $bitlove_feed_url;
		} else {
			$subscribe_url = $feed->get_subscribe_url();
			$url = 'http://api.bitlove.org/feed-lookup.json?url=' . $subscribe_url;

			$curl = new \Podlove\Http\Curl();
			$curl->request( $url, array(
				'headers' => array( 'Content-type'  => 'application/json' )
			) );
			$response = $curl->get_response();

			if (!$curl->isSuccessful())
				return array();

			$decoded_answer = get_object_vars(json_decode($response['body']));
 
			// The response is always the first array element
			$bitlove_url = $decoded_answer[$subscribe_url][0]; 

			if ($bitlove_url) {
				set_transient( $cache_key, $bitlove_url, DAY_IN_SECONDS );
			}

			return $bitlove_url; 
		}
	}

	public function add_feed_model_extension() {
		\Podlove\Model\Feed::property( 'bitlove', 'TINYINT(1)' );
	}

	public function was_activated() {
		global $wpdb;

		if( get_option("_podlove_added_bitlove_to_feed_model") !== 1 ) {
			$wpdb->query( sprintf(
				"ALTER TABLE `%s` ADD COLUMN `bitlove` TINYINT(1) DEFAULT '0'",
				\Podlove\Model\Feed::table_name()
			) );
			update_option( "_podlove_added_bitlove_to_feed_model", 1 );
		}
	}

	public function was_deactivated() {
		$feeds = \Podlove\Model\Feed::all("WHERE `bitlove` = '1'");

		foreach ($feeds as $feed) {
			delete_transient( "podlove_bitlove_feed_url_" . $feed->id );
		}
		
	}

	public function enable_bitlove_flag_for_feed( $wrapper ) {
		$wrapper->checkbox( 'bitlove', array(
			'label'       	=> __( 'Available via Bitlove?', 'podlove-podcasting-plugin-for-wordpress' ),
			'description' 	=> __( 'The Bitlove feed will be added to your list of feeds.', 'podlove-podcasting-plugin-for-wordpress' ) . '<p class="podlove-bitlove-status"></p>',
			'default'     	=> false,
			'html' 	=> array( 'data-feed-id' => $_GET['feed'] )
		) );
	}
}
