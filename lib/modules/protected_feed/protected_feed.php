<?php
namespace Podlove\Modules\ProtectedFeed;
use Podlove\Model;

class Protected_Feed extends \Podlove\Modules\Base {

	protected $module_name = 'Protected Feeds';
	protected $module_description = 'Protect feeds using HTTP Basic Authentication or require login credentials from WordPress. Warning: few clients support feed authentication.';
	protected $module_group = 'web publishing';

	public function load() {
		add_action('pre_get_posts', [$this, 'inject_feed_protection']);		
		add_action('podlove_feed_settings_bottom', [$this, 'inject_feed_setting']);
		add_filter('podlove_feed_list_table_columns', [$this, 'add_feed_list_protected_column']);
	}

	public function inject_feed_protection() {
		global $wp_query;	

		if (!is_feed())
			return;

		$feedname = get_query_var('feed');
		$feed = \Podlove\Model\Feed::find_one_by_property('slug', $feedname);
		
		if ( isset($feed) && $feed->protected == 1 ) {
			if ( !isset( $_SERVER['PHP_AUTH_USER'] ) || !isset( $_SERVER['PHP_AUTH_PW'] ) ) {
				self::send_authentication_headers();
			} else {
				switch ($feed->protection_type) {
					case '0' :
						// A local User/PW combination is set
						if ( $_SERVER['PHP_AUTH_USER'] == $feed->protection_user && $_SERVER['PHP_AUTH_PW'] == $feed->protection_password) {
							// let the script continue
							\Podlove\Feeds\check_for_and_do_compression();
						} else {
							self::send_authentication_headers();
						}
					break;
					case '1' :
						// The WordPress User db is used for authentification
						if ( !username_exists($_SERVER['PHP_AUTH_USER'] ) ) {
							self::send_authentication_headers();
						} else {
							$userinfo = get_user_by( 'login', $_SERVER['PHP_AUTH_USER'] );
							if ( wp_check_password( $_SERVER['PHP_AUTH_PW'], $userinfo->data->user_pass, $userinfo->ID ) ) {
								// let the script continue
								\Podlove\Feeds\check_for_and_do_compression();
							} else {
								self::send_authentication_headers();
							}
						}
					break;
					default :
						exit; // If the feed is protected and no auth method is selected exit the script
					break;
				}
			}
		} else {
			// compress unprotected feeds
			\Podlove\Feeds\check_for_and_do_compression();
		}
	}

	public static function send_authentication_headers() {
		header('WWW-Authenticate: Basic realm="This feed is protected. Please login."');
		header('HTTP/1.1 401 Unauthorized');
		exit;
	}

	public function inject_feed_setting($wrapper) {
		$wrapper->subheader( __( 'Protection', 'podlove-podcasting-plugin-for-wordpress' ) );

		$wrapper->checkbox( 'protected', array(
			'label'       => __( 'Protect feed ', 'podlove-podcasting-plugin-for-wordpress' ),
			'description' => __( 'The feed will be protected by HTTP Basic Authentication.', 'podlove-podcasting-plugin-for-wordpress' ),
			'default'     => false
		) );

		$wrapper->select( 'protection_type', array(
			'label'       => __( 'Method', 'podlove-podcasting-plugin-for-wordpress' ),
			'description' => __( '', 'podlove-podcasting-plugin-for-wordpress' ),
			'options' => array(
				'0' => 'Custom Login',
				'1' => 'WordPress User database'
			),
			'default' => -1,
			'please_choose' => true
		) );

		$wrapper->string( 'protection_user', array(
			'label'       => __( 'Username', 'podlove-podcasting-plugin-for-wordpress' ),
			'description' => '',
			'html'        => array( 'class' => 'regular-text required' )
		) );

		$wrapper->string( 'protection_password', array(
			'label'       => __( 'Password', 'podlove-podcasting-plugin-for-wordpress' ),
			'description' => '',
			'html'        => array( 'class' => 'regular-text required' )
		) );
	}

	public function add_feed_list_protected_column($columns) {
		
		$keys = array_keys($columns);
		$insertIndex = array_search('discoverable', $keys) + 1; // after discoverable column

		// insert at that index
		$columns = array_slice($columns, 0, $insertIndex, true) +
		       array("protected" => __('Protected', 'podlove-podcasting-plugin-for-wordpress')) +
		       array_slice($columns, $insertIndex, count($columns) - 1, true);

		return $columns;
	}
}
