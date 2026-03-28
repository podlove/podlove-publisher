<?php

namespace Podlove\Modules\Auphonic;

use Podlove\Http;
use Podlove\Log;

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

                if (!$decoded_user || !isset($decoded_user->data)) {
                    Log::get()->addWarning('Auphonic user verification returned unexpected payload.', [
                        'token_debug' => self::token_debug(self::$auth_key),
                        'body' => is_array($response) && isset($response['body']) ? $response['body'] : null,
                    ]);
                }

                return $decoded_user ? $decoded_user : false;
            }

            Log::get()->addWarning('Auphonic user verification failed.', [
                'token_debug' => self::token_debug(self::$auth_key),
                'response' => $response,
            ]);

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

            Log::get()->addWarning('Auphonic preset fetch failed.', [
                'token_debug' => self::token_debug(self::$auth_key),
                'response' => $response,
            ]);

            return [];
        }, 10);
    }

    private static function token_debug($token)
    {
        if (!$token) {
            return [
                'present' => false,
            ];
        }

        return [
            'present' => true,
            'length' => strlen($token),
            'sha1_prefix' => substr(sha1($token), 0, 12),
        ];
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
