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

    public function getToken()
    {
        return $this->token;
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
        $payload = wp_json_encode(['feeds' => $feeds]);

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

    public function create_image_preset($template_name, $modifications = [])
    {
        $payload = wp_json_encode(['template' => $template_name, 'modifications' => $modifications]);

        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/image/preset', $this->params([
            'method' => 'POST',
            'body' => $payload,
        ]));

        do_action('podlove_plus_api_create_image_preset');

        return $curl->get_response();
    }

    public function create_file_upload($filename)
    {
        // TODO: maybe podcast_id is not the right id here. think about what we
        // need and what's best suited.
        $query = http_build_query([
            'filename' => $filename,
            'podcast_id' => 1
        ]);

        $curl = new Http\Curl();
        $curl->request(
            $this->module::base_url().'/api/rest/v1/files/upload/new?'.$query,
            $this->params([
                'method' => 'POST'
            ])
        );

        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            $decoded_response = json_decode($response['body']);

            return $decoded_response->url ?? false;
        }

        return false;
    }

    /**
     * List all podcasts for the connected account in PLUS.
     */
    public function list_podcasts()
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/podcasts', $this->params());
        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            return json_decode($response['body']) ?? false;
        }

        return false;
    }

    /**
     * Update podcast title in PLUS.
     *
     * This function will create a podcast if it doesn't exist yet.
     */
    public function upsert_podcast_title(string $guid, string $title)
    {
        $podcast = $this->get_podcast_by_guid($guid);

        if ($podcast) {
            $this->update_podcast($podcast->id, ['title' => $title]);
        } else {
            $this->create_podcast($guid, ['title' => $title]);
        }
    }

    /**
     * Get PLUS podcast by guid.
     */
    public function get_podcast_by_guid(string $guid)
    {
        $podcasts = $this->list_podcasts();

        $matching_podcast = array_filter($podcasts, function ($podcast) use ($guid) {
            return $podcast->guid === $guid;
        });

        return array_values($matching_podcast)[0] ?? false;
    }

    /**
     * Get PLUS podcast by id.
     */
    public function get_podcast(int $podcast_id)
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/podcasts/'.$podcast_id, $this->params());
        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            $decoded_podcast = json_decode($response['body']);

            return $decoded_podcast ?? false;
        }

        return false;
    }

    public function update_podcast(int $podcast_id, array $data)
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/podcasts/'.$podcast_id, $this->params([
            'method' => 'PUT',
            'body' => wp_json_encode(['podcast' => $data]),
        ]));

        return $curl->get_response();
    }

    /**
     * Create a podcast in PLUS.
     *
     * Currently only supports the required fields: `guid` and `title`.
     */
    public function create_podcast(string $guid, array $data)
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/podcasts', $this->params([
            'method' => 'POST',
            'body' => wp_json_encode(['guid' => $guid, 'title' => $data['title']]),
        ]));

        return $curl->get_response();
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
