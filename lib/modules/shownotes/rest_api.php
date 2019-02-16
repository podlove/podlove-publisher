<?php
namespace Podlove\Modules\Shownotes;

use Podlove\Http\Curl;
use Podlove\Model\Episode;
use Podlove\Modules\Shownotes\Model\Entry;

class REST_API
{
    const api_namespace = 'podlove/v1';
    const api_base      = 'shownotes';

    // todo: delete
    // todo: update -- not sure I even need this except "save unfurl data"

    public function register_routes()
    {
        register_rest_route(self::api_namespace, self::api_base, [
            [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'args'     => [
                    'episode_id' => [
                        'description' => 'Limit result set by episode.',
                        'type'        => 'integer',
                    ],
                ],
            ],
            [
                'methods'  => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
            ],
        ]);
        register_rest_route(self::api_namespace, self::api_base . '/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the object.'),
                    'type'        => 'integer',
                ],
            ],
            [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
            ],
            [
                'methods'  => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
            ],
        ]);
        register_rest_route(self::api_namespace, self::api_base . '/(?P<id>[\d]+)/unfurl', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the object.'),
                    'type'        => 'integer',
                ],
            ],
            [
                'methods'  => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'unfurl_item'],
            ],
        ]);
    }

    public function get_items($request)
    {
        $episode_id = $request['episode_id'];

        if (!$episode_id) {
            return new \WP_Error(
                'podlove_rest_missing_episode_id',
                'episode_id is required',
                ['status' => 400]
            );
        }

        $entries = Entry::find_all_by_property('episode_id', $episode_id);
        $entries = array_map(function ($entry) {
            return $entry->to_array();
        }, $entries);

        $response = rest_ensure_response($entries);

        return $response;
    }

    public function create_item($request)
    {
        if (!$request["episode_id"]) {
            return new \WP_Error(
                'podlove_rest_missing_episode_id',
                'episode_id is required',
                ['status' => 400]
            );
        }

        $episode = Episode::find_by_id($request["episode_id"]);

        if (!$episode) {
            return new \WP_Error(
                'podlove_rest_episode_not_found',
                'episode does not exist',
                ['status' => 400]
            );
        }

        $original_url = esc_sql($request['original_url']);
        $episode_id   = (int) $episode->id;

        if (Entry::find_one_by_where("episode_id = $episode_id AND original_url = '$original_url'")) {
            return new \WP_Error(
                'podlove_rest_duplicate_entry',
                'a shownotes entry for this URL exists already',
                ['status' => 400]
            );
        }

        $entry = new Entry;
        foreach (Entry::property_names() as $property) {
            if (isset($request[$property]) && $request[$property]) {
                $entry->$property = $request[$property];
            }
        }
        $entry->episode_id = $episode->id;

        if (!$entry->save()) {
            return new \WP_Error(
                'podlove_rest_create_failed',
                'error when creating entry',
                ['status' => 400]
            );
        }

        $response = rest_ensure_response($entry->to_array());
        $response->set_status(201);

        $url = sprintf('%s/%s/%d', self::api_namespace, self::api_base, $entry->id);
        $response->header('Location', rest_url($url));

        return $response;
    }

    public function get_item($request)
    {
        $entry = Entry::find_by_id($request['id']);
        if (is_wp_error($entry)) {
            return $entry;
        }
        $response = rest_ensure_response($entry->to_array());

        return $response;
    }

    public function delete_item($request)
    {
        $entry = Entry::find_by_id($request['id']);
        if (is_wp_error($entry)) {
            return $entry;
        }
        $response = rest_ensure_response(['deleted' => true]);

        if (!$entry) {
            return new \WP_Error('podlove_rest_already_deleted', 'The entry has already been deleted.', ['status' => 410]);
        }

        $success = $entry->delete();

        if (!$success) {
            return new \WP_Error('podlove_rest_cannot_delete', 'The entry cannot be deleted.', ['status' => 500]);
        }

        return $response;
    }

    public function unfurl_item($request)
    {
        $entry = Entry::find_by_id($request['id']);
        if (is_wp_error($entry)) {
            return $entry;
        }

        $url = $entry->original_url;

        $unfurl_endpoint = "http://unfurl.eric.co.de/unfurl?&url=https%3A%2F%2Fde.wikipedia.org%2Fwiki%2FDivisor";
        $curl            = new Curl;
        $curl->request(add_query_arg("url", $url, $unfurl_endpoint), [
            'headers' => ['Content-type' => 'application/json'],
            'timeout' => 20,
        ]);

        $response = $curl->get_response();

        if (!$curl->isSuccessful()) {

            $entry->state = 'failed';
            $entry->save();

            error_log(print_r($response, true));

            return new \WP_Error(
                'podlove_rest_unfurl_failed',
                'error when unfurling entry',
                ['status' => 404]
            );
        }

        $data = json_decode($response['body'], true);

        // remove "data:..." images because they are too huge to store in database
        $url_size_threshold = 1000;

        if (strlen($data['icon']['url']) > $url_size_threshold) {
            unset($data['icon']);
        }

        foreach ($data['providers']['misc']['icons'] as $index => $icon) {
            if (strlen($icon['url']) > $url_size_threshold) {
                unset($data['providers']['misc']['icons'][$index]);
            }
        }

        $entry->unfurl_data = $data;
        $entry->state       = 'fetched';
        $entry->url         = $data['url'];
        $entry->title       = $data['title'];
        $entry->description = $data['description'];
        $entry->site_name   = $data['site_name'];
        $entry->site_url    = $data['site_url'];
        $entry->icon        = $data['icon']['url'];
        $success            = $entry->save();

        $response = rest_ensure_response($entry->to_array());

        return $response;

    }
}
