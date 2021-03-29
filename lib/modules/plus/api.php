<?php

namespace Podlove\Modules\Plus;

use Podlove\Http;

class API
{
    private $module;
    private $token;

    public function __construct($module, $token)
    {
        $this->module = $module;
        $this->token = $token;
    }

    public function get_me()
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/me', $this->params());
        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            $decoded_user = json_decode($response['body']);

            return $decoded_user ?? false;
        }

        return false;
    }

    public function get_account_id()
    {
        $cache = \Podlove\Cache\TemplateCache::get_instance();

        return $cache->cache_for('plus_account_id', function () {
            $user = $this->get_me();

            if (!$user) {
                return;
            }

            return $user->account_id;
        }, 60);
    }

    public function list_feeds()
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/feeds', $this->params());
        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            return json_decode($response['body']) ?? false;
        }

        return false;
    }

    public function push_feeds($feeds)
    {
        $payload = json_encode(['feeds' => $feeds]);

        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/feeds', $this->params([
            'method' => 'POST',
            'body' => $payload,
        ]));

        do_action('podlove_plus_api_push_feeds');

        return $curl->get_response();
    }

    public function get_proxy_url($origin_url)
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/feeds/proxy_url?url='.urlencode($origin_url), $this->params());
        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            $decoded_response = json_decode($response['body']);

            return $decoded_response->url ?? false;
        }

        return false;
    }

    private function params($params = [])
    {
        return array_merge([
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->token,
            ],
        ], $params);
    }
}
