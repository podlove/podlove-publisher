<?php
namespace Podlove\Tracking;

class Debug {

	public static function rewrites_exist() {
		global $wp_rewrite;

		$top_rewrite_patterns = array_keys($wp_rewrite->extra_rules_top);
		$podlove_rewrites = array_filter($top_rewrite_patterns, function($pattern) {
			return stristr($pattern, "^podlove/file/") !== false;
		});
		
		return count($podlove_rewrites) > 0; 
	}

	public static function is_consistent_https_chain($public_url, $actual_url) {

		// if the site doesn't run SSL it doesn't matter what the actual_url structure is
		if (!self::startswith($public_url, 'https'))
			return true;

		// if the site runs SSL, the files *must* be served with SSL, too
		return self::startswith($actual_url, 'https');
	}

	public static function url_resolves_correctly($start_url, $target_url) {
		$max_redirects = 5;
		$url_chain = array($start_url);

		while ($new_location = self::follow_url(end($url_chain))) {
			$url_chain[] = $new_location;
			$max_redirects--;

			if ($max_redirects <= 0)
				break;
		}

		$final_url = end($url_chain);

		return stristr($final_url, $target_url) !== false;
	}

	private static function startswith($haystack, $needle) {
	    return substr($haystack, 0, strlen($needle)) === $needle;
	}

	private static function follow_url($url) {
		if ( ! function_exists( 'curl_exec' ) )
			return false;

		$curl = curl_init();
		$curl_version = curl_version();

		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ); // make curl_exec() return the result
		curl_setopt( $curl, CURLOPT_HEADER, true );         // header only
		curl_setopt( $curl, CURLOPT_NOBODY, true );         // return no body; HTTP request method: HEAD
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, (\Podlove\get_setting('website', 'ssl_verify_peer') == 'on')); // Don't check SSL certificate in order to be able to use self signed certificates
		curl_setopt( $curl, CURLOPT_FAILONERROR, true );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 3 );          // HEAD requests shouldn't take > 2 seconds

		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, false ); // follow redirects
		curl_setopt( $curl, CURLOPT_MAXREDIRS, 0 );          // maximum number of redirects

		curl_setopt( $curl, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent() );
		
		$response        = curl_exec( $curl );
		$response_header = curl_getinfo( $curl );
		$error           = curl_error( $curl );
		curl_close( $curl );

		if (isset($response_header['redirect_url']) && $response_header['redirect_url']) {
			return $response_header['redirect_url'];
		} else {
			return false;
		}
		
		// return array(
		// 	'header'   => $response_header,
		// 	'response' => $response,
		// 	'error'    => $error
		// );
	}
}