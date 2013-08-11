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

	public function request( $url, $params ) {

		$defaults = array(
			'user-agent' => self::user_agent(),
			'stream'     => false,
			'decompress' => false,
			'filename'   => null
		);

		$params = wp_parse_args( $params, $defaults );

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

}