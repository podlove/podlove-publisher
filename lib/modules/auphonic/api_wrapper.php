<?php
namespace Podlove\Modules\Auphonic;

use \Podlove\Http;

class API_Wrapper {

	private $module;
	public static $auth_key;
	
	public function __construct(\Podlove\Modules\Auphonic\Auphonic $module)
	{
		$this->module = $module;
		self::$auth_key = $this->module->get_module_option('auphonic_api_key');
	}

	public function fetch_authorized_user()
	{
		return self::cache_for('podlove_auphonic_user', function() {
			$curl = new Http\Curl();
			$curl->request( 'https://auphonic.com/api/user.json', array(
				'headers' => array(
					'Content-type'  => 'application/json',
					'Authorization' => 'Bearer ' . API_Wrapper::$auth_key
				)
			) );
			$response = $curl->get_response();

			if ($curl->isSuccessful()) {
				$decoded_user = json_decode( $response['body'] );
				return $decoded_user ? $decoded_user : FALSE;
			} else {
				return false;
			}
		});
	}

    public function fetch_presets() {
    	return self::cache_for('podlove_auphonic_presets', function() {
			$curl = new Http\Curl();
			$curl->request( 'https://auphonic.com/api/presets.json', array(
				'headers' => array(
					'Content-type'  => 'application/json',
					'Authorization' => 'Bearer ' . API_Wrapper::$auth_key
				)
			) );
			$response = $curl->get_response();

			if ($curl->isSuccessful()) {
				return json_decode( $response['body'] );
			} else {
				return array();
			}
    	});
    }

	private static function cache_for($cache_key, $callback, $duration = 31536000 /* 1 year */)
	{
		if (($value = get_transient($cache_key)) !== FALSE) {
			return $value;
		} else {
			$value = call_user_func($callback);
			
			if ($value !== FALSE)
				set_transient($cache_key, $value, $duration);

			return $value;
		}
	}
}