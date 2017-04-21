<?php
/**
 * This module handles the FeedPress auth methods and provides access to 
 * all Feeds analyzed by FeedPress. To fetch the data two static methods 
 * can be used:
 *
 * fetch_feeds_analyzed_by_feed_press();
 * ----------------------------------------------------------
 * This function returns a list of feeds, which are analyzed by FeedPress AND are 
 * provided by the present Podlove Publisher instance.
 * 
 * fetch_feed_press_feed_info( string $feedname );
 * ----------------------------------------------------------
 * This function returns advanced statistics for $feedname for the last 30 days.
 * 
 */

namespace Podlove\Modules\FeedPress;
use \Podlove\Model;
use \Podlove\Http;

class Feed_Press extends \Podlove\Modules\Base {

    protected $module_name = 'FeedPress';
    protected $module_description = 'FeedPress provides accurate and frequently updated analytics that bloggers and podcasters have come to trust. This module provides access the latest statistics.';
    protected $module_group = 'external services';
	
    public function load() {
        if ( isset( $_GET["page"] ) && $_GET["page"] == "podlove_settings_modules_handle") {
            add_action('admin_bar_init', array( $this, 'check_code'));
        }

        $this->register_settings();
    }

    /**
     * Fetches Statistics for a specified feed analyzed by FeedPress
     *
     * The Statistic Objects are returned with these properties:
     *     - (int) day (Unix timestamp)
     *     - (int) greader
     *     - (int) other
     *     - (int) direct
     *     - (int) newsletter
     * 
     * @param  string $token    [FeedPress API Token)
     * @param  string $feedname [Name of FeedPress Feed]
     * 
     * @return array            [Array of the Feed Stats for the last 30 days]
     */
    public static function fetch_feed_press_feed_info($feedname=FALSE) {
        if ( ! $feedname || ! $token = get_option( 'podlove_module_feed_press' )['feed_press_api_key'] )
            return;

        $curl = new Http\Curl();
        $curl->request( 'https://api.feed.press/feeds/subscribers.json?key=58eaa1be7018c&token=' . $token . '&feed=' . $feedname, array(
            'headers' => array(
                'Content-type'  => 'application/json'
            )
        ) );
        $response = $curl->get_response();

         if ($curl->isSuccessful()) {
            return json_decode( $response['body'] );
        } else {
            return FALSE;
        }
    }

    /**
     * Lists all Publisher Feeds analyzed by FeedPress
     *
     * Podlove and FeedPress Feeds are matched by their URLs.
     *
     * The FeedPress Objects are returned with these properties:
     *     - (int)      id
     *     - (string)   name
     *     - (int)      subscribers
     *     - (string)   url
     *     - (string)   original_url
     * 
     * @return array [all Feeds (Objects) which are analyzed by FeedPress]
     */
    public static function fetch_feeds_analyzed_by_feed_press() {
        if ( ! $token = get_option( 'podlove_module_feed_press' )['feed_press_api_key'] )
            return;

        if ( ! $feed_press_feeds = self::fetch_feed_press_account_info($token)->feeds )
            return;

        $publisher_feeds = self::get_subscription_urls();
        $feeds_analyzed_by_feed_press = [];

        foreach ($feed_press_feeds as $feed) {
            if ( in_array($feed->url, $publisher_feeds) ) {
                $feeds_analyzed_by_feed_press[$feed->name] = $feed;
            } else {
                continue;
            } 
        }

        return $feeds_analyzed_by_feed_press;
    }

    /**
     * Fetches URLs of all public feeds provided by the current Podlove instance
     * 
     * @return array 
     */
    private static function get_subscription_urls() {
        $feeds = [];

        foreach (\Podlove\Model\Feed::all() as $key => $feed) {
            if ( !$feed->enable || !$feed->discoverable )
                continue;

            /**
             * @todo Think about if this check is useful. Couldn't it be that the redirected URLs match the FeedPress ones?
             */
            if ( $feed->redirect_http_status == '0' ) {
                $feeds[] = $feed->get_subscribe_url();        
            } else {
                $feeds[] = $feed->redirect_url;
            }
        }  

        return $feeds;
    }

    public function register_settings() {
        if ( ! self::is_module_settings_page() )
            return;

        if ( $this->get_module_option('feed_press_api_key') == "" ) {
            $auth_url = "https://api.feed.press/login.json?key=58eaa1be7018c&callback=" . urlencode(get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle') . "&response_type=code";
            $description = '<i class="podlove-icon-remove"></i> '
                         . __( 'You need to allow Podlove Publisher to access your FeedPress account. You will be redirected to this page once the auth process completed.', 'podlove-podcasting-plugin-for-wordpress' )
                         . '<br><a href="' . $auth_url . '" class="button button-primary">' . __( 'Authorize now', 'podlove-podcasting-plugin-for-wordpress' ) . '</a>';
        } else {
            $user = $this->fetch_authorized_user();

            if ( isset($user) && $user ) {
                $description = '<i class="podlove-icon-ok"></i> '
                             . sprintf(
                                __( 'You are logged in as %s. If you want to logout, click %shere%s.', 'podlove-podcasting-plugin-for-wordpress' ),
                                '<strong>' . $user . '</strong>',
                                '<a href="' . admin_url( 'admin.php?page=podlove_settings_modules_handle&reset_feed_press_auth_code=1' ) . '">',
                                '</a>'
                            );
            } else {
                $description = '<i class="podlove-icon-remove"></i> '
                             . sprintf(
                                __( 'Something went wrong with the FeedPress connection. Please reset the connection and authorize again. To do so click %shere%s', 'podlove-podcasting-plugin-for-wordpress' ),
                                '<a href="' . admin_url( 'admin.php?page=podlove_settings_modules_handle&reset_feed_press_auth_code=1' ) . '">',
                                '</a>'
                            );
            }
        }

        $this->register_option( 'feed_press_api_key', 'hidden', array(
            'label'       => __( 'Authorization', 'podlove-podcasting-plugin-for-wordpress' ),
            'description' => $description,
            'html'        => array( 'class' => 'regular-text podlove-check-input' )
        ) );
    }

    public function check_code() {
        if ( self::is_module_settings_page() && isset( $_GET["token"] ) && $_GET["token"] ) {
            if( $this->get_module_option('feed_press_api_key') == "" && ctype_alnum($_GET["token"]) ) {
                $this->update_module_option('feed_press_api_key', $_GET["token"]);
                header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');

            }
        }

        if ( isset( $_GET["reset_feed_press_auth_code"] ) && $_GET["reset_feed_press_auth_code"] == "1" ) {
            $this->update_module_option('feed_press_api_key', "");
            delete_transient('podlove_feed_press_user');
            header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
        }
    }

    public static function fetch_feed_press_account_info( $token=FALSE ) {
        if ( ! $token )
            return FALSE;

        $curl = new Http\Curl();
        $curl->request( 'https://api.feed.press/account.json?key=58eaa1be7018c&token=' . $token, array(
            'headers' => array(
                'Content-type'  => 'application/json'
            )
        ) );
        $response = $curl->get_response();

         if ($curl->isSuccessful()) {
            return json_decode( $response['body'] );
        } else {
            return FALSE;
        }
    }

    /**
    * Fetch name of logged in user via FeedPress API.
    *
    * Cached in transient "podlove_feed_press_user".
    * 
    * @return string
    */
    public function fetch_authorized_user() {
        $cache_key = 'podlove_feed_press_user';

        if ( ( $user = get_transient( $cache_key ) ) !== FALSE ) {
            return $user;
        } else {
            if ( ! ( $token = $this->get_module_option('feed_press_api_key') ) )
                return "";

            if ( $decoded_user = self::fetch_feed_press_account_info($token) ) {
                $user = $decoded_user->login ? $decoded_user->login : FALSE;
                set_transient( $cache_key, $user, 60*60*24*365 ); // 1 year, we devalidate manually
                return $user;
            } else {
                return FALSE;
            }            
        }
    }
}
