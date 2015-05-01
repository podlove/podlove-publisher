<?php
namespace Podlove\Http;

use Podlove\Log;

/**
 * Wrapper for WordPress' WP_Http_Curl class.
 */
class Curl {

	// WP_Http_Curl instance
	private $curl;

	// request call parameters
	private $request = array();

	private $response = null;

	public function __construct() {
		$this->curl = new \WP_Http_Curl();
	}

	public function request( $url, $params = array() ) {

		$defaults = array(
			'user-agent' => self::user_agent(),
			'stream'     => false,
			'decompress' => false,
			'filename'   => null,
			'sslcertificates' => ABSPATH . WPINC . '/certificates/ca-bundle.crt'  // i guess this is just a missing default in the current wp curl lib
		);

		$params = wp_parse_args( $params, $defaults );

		if ( !self::curl_can_follow_redirects() )
			$url = self::resolve_redirects($url, 5);

		$this->response = $this->curl->request( $url, $params );

		if ( is_wp_error($this->response) ) {
			Log::get()->addError( 'Curl error', array(
				'url' => $url,
				'error' => $this->response->get_error_message()
			) );
		} elseif (substr($this->response['response']['code'], 0, 1) >= 4) {
			Log::get()->addError( 'Curl error', array(
				'url' => $url,
				'response code' => $this->response['response']['code']
			) );
			Log::get()->addDebug(print_r($this->response, true));
		}
	}

	public function isSuccessful()
	{
		return
			$this->response &&               // request has been made
			!is_wp_error($this->response) && // there was no error
			substr($this->response['response']['code'], 0, 1) < 4; // 1xx 2xx or 3xx
	}

	public function get_response() {
		return $this->response;
	}

	/**
	 * Podlove User Agent for cURL requests.
	 * 
	 * @return string
	 */
	public static function user_agent() {

		$curl_version = curl_version();

		return sprintf(
			'PHP/%s (; ) cURL/%s(OpenSSL/%s; zlib/%s) Wordpress/%s (; ) %s/%s (; )',
			phpversion(),
			$curl_version['version'],
			$curl_version['ssl_version'],
			$curl_version['libz_version'],
			get_bloginfo( 'version' ),
			\Podlove\get_plugin_header( 'Name' ),
			\Podlove\get_plugin_header( 'Version' )
		);
	}

	/**
	 * Manually resolve redirects.
	 * 
	 * Some server configurations can't deal with cURL CURLOPT_FOLLOWLOCATION
	 * setting. This method resolves a URL without using that setting.
	 * 
	 * @param  string  $url               URL to resolve
	 * @param  integer $maximum_redirects Maximum redirects. Default: 5.
	 * @return string                     Final URL
	 */
	public static function resolve_redirects($url, $maximum_redirects = 5) {
		$curl = new Curl;
		$curl->request($url, ['method' => 'HEAD', '_redirection' => 0]);
		$response = $curl->get_response();

		$http_code = $response['response']['code'];
		$location  = isset($response['headers']['location']) ? $response['headers']['location'] : NULL;

		if ($http_code >= 300 && $http_code <= 400 && $location && $maximum_redirects > 0)
			return self::resolve_redirects($location, $maximum_redirects - 1);

		return $url;
	}

	/**
	 * Check for CURLOPT_FOLLOWLOCATION bug.
	 * 
	 * If either safe_mode is on or an open_basedir path is set, 
	 * CURLOPT_FOLLOWLOCATION does not work.
	 * 
	 * @see  https://stackoverflow.com/questions/2511410/curl-follow-location-error
	 * @see  https://stackoverflow.com/questions/19539922/php-can-curlopt-followlocation-and-open-basedir-be-used-together
	 * 
	 * @return bool
	 */
	public static function curl_can_follow_redirects() {
		return !(ini_get('open_basedir') || ini_get('safe_mode'));
	}
}