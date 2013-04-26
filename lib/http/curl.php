<?php
namespace Podlove\Http;

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
			'user-agent' => $this->get_user_agent(),
			'stream'     => false,
			'decompress' => false,
			'filename'   => null
		);

		$params = wp_parse_args( $params, $defaults );

		$this->response = $this->curl->request( $url, $params );
	}

	public function get_response() {
		return $this->response;
	}

	private function get_user_agent() {

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