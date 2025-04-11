<?php

namespace Podlove\Modules\Plus;

use Podlove\Http;
use Podlove\Model\Podcast;

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

        return $this->handle_json_response($curl);
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

        return $this->handle_json_response($curl);
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

        $response = $this->handle_json_response($curl);
        if ($response) {
            return $response->url ?? false;
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
        $query = http_build_query([
            'filename' => $filename,
            'podcast_guid' => (string) Podcast::get()->guid
        ]);

        $curl = new Http\Curl();
        $curl->request(
            $this->module::base_url().'/api/rest/v1/files/upload/new?'.$query,
            $this->params([
                'method' => 'POST'
            ])
        );

        $response = $this->handle_json_response($curl);
        if ($response) {
            return $response->url ?? false;
        }

        return false;
    }

    public function complete_file_upload($filename)
    {
        $query = http_build_query([
            'filename' => $filename,
            'podcast_guid' => (string) Podcast::get()->guid
        ]);

        $curl = new Http\Curl();
        $curl->request(
            $this->module::base_url().'/api/rest/v1/files/upload/complete?'.$query,
            $this->params([
                'method' => 'POST'
            ])
        );

        $response = $this->handle_json_response($curl);
        if ($response) {
            return $response->file ?? false;
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

        return $this->handle_json_response($curl);
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
            return $this->update_podcast($podcast->id, ['title' => $title]);
        }

        return $this->create_podcast($guid, ['title' => $title]);
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

        return $this->handle_json_response($curl);
    }

    public function update_podcast(int $podcast_id, array $data)
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/podcasts/'.$podcast_id, $this->params([
            'method' => 'PUT',
            'body' => wp_json_encode(['podcast' => $data]),
        ]));

        return $this->handle_json_response($curl);
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

        return $this->handle_json_response($curl);
    }

    /**
     * Handles common JSON response processing.
     *
     * @param Http\Curl $curl The curl object with the executed request
     *
     * @return mixed Decoded JSON object or false on failure
     */
    private function handle_json_response($curl)
    {
        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            return json_decode($response['body']) ?? false;
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
