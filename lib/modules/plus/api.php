<?php
namespace Podlove\Modules\Plus;

use \Podlove\Http;

class API
{
    private $module;
    private $token;

    public function __construct($module, $token)
    {
        $this->module = $module;
        $this->token  = $token;
    }

    public function get_me()
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url() . '/api/rest/v1/me', array(
            'headers' => array(
                'Content-type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
            ),
        ));
        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            $decoded_user = json_decode($response['body']);
            return $decoded_user ? $decoded_user : false;
        } else {
            return false;
        }

    }
}
