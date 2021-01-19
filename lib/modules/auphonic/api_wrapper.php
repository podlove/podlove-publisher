<?php

namespace Podlove\Modules\Auphonic;

use Podlove\Http;

class API_Wrapper
{
    public static $auth_key;
    private $module;

    public function __construct(Auphonic $module)
    {
        $this->module = $module;
        self::$auth_key = $this->module->get_module_option('auphonic_api_key');
    }

    public function fetch_authorized_user()
    {
        return self::cache_for('podlove_auphonic_user', function () {
            $curl = new Http\Curl();
            $curl->request('https://auphonic.com/api/user.json', [
                'headers' => [
                    'Content-type' => 'application/json',
                    'Authorization' => 'Bearer '.API_Wrapper::$auth_key,
                ],
            ]);
            $response = $curl->get_response();

            if ($curl->isSuccessful()) {
                $decoded_user = json_decode($response['body']);

                return $decoded_user ? $decoded_user : false;
            }

            return false;
        });
    }

    public function fetch_presets()
    {
        return self::cache_for('podlove_auphonic_presets', function () {
            $curl = new Http\Curl();
            $curl->request('https://auphonic.com/api/presets.json', [
                'headers' => [
                    'Content-type' => 'application/json',
                    'Authorization' => 'Bearer '.API_Wrapper::$auth_key,
                ],
            ]);
            $response = $curl->get_response();

            if ($curl->isSuccessful()) {
                return json_decode($response['body']);
            }

            return [];
        }, DAY_IN_SECONDS);
    }

    private static function cache_for($cache_key, $callback, $duration = 31536000 /* 1 year */)
    {
        if (($value = get_transient($cache_key)) !== false) {
            return $value;
        }
        $value = call_user_func($callback);

        if ($value !== false) {
            set_transient($cache_key, $value, $duration);
        }

        return $value;
    }
}
