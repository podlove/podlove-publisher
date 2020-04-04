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
        $curl->request($this->module::base_url() . '/api/rest/v1/me', $this->params());
        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            $decoded_user = json_decode($response['body']);
            return $decoded_user ? $decoded_user : false;
        } else {
            return false;
        }
    }

    public function push_feeds($feeds)
    {
        $payload = json_encode(["feeds" => $feeds]);

        $curl = new Http\Curl();
        $curl->request($this->module::base_url() . '/api/rest/v1/feeds', $this->params([
            'method' => 'POST',
            'body'   => $payload,
        ]));
        return $curl->get_response();
    }

    private function params($params = [])
    {
        return array_merge([
            'headers' => [
                'Content-type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
            ],
        ], $params);
    }
}
