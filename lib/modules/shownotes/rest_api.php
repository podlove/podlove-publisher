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
            [
                'methods'  => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
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
        register_rest_route(self::api_namespace, self::api_base . '/osf', [
            [
                'methods'  => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'import_osf'],
            ],
        ]);
        register_rest_route(self::api_namespace, self::api_base . '/html', [
            [
                'methods'  => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'import_html'],
            ],
        ]);
    }

    public function import_html($request)
    {
        $post_id = $request['post_id'];

        if (!$episode = \Podlove\Model\Episode::find_or_create_by_post_id($post_id)) {
            return new \WP_Error(
                'podlove_rest_html_no_episode',
                'episode cannot be found',
                ['status' => 400]
            );
        }

        $dom                     = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $valid                   = $dom->loadHTML($request['html']);

        if (!$valid) {
            return new \WP_Error(
                'podlove_rest_html_unreadable',
                'html could not be parsed',
                ['status' => 400]
            );
        }

        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query("//a | //h1 | //h2 | //h3 | //h4 | //h5 | //h6") as $element) {
            if ($element->tagName == "a") {
                $request = new \WP_REST_Request('POST', '/podlove/v1/shownotes');
                $request->set_query_params([
                    'episode_id'   => $episode->id,
                    'original_url' => $element->getAttribute("href"),
                    'data'         => [
                        'title' => $element->textContent,
                    ],
                    'type'         => 'link',
                ]);
                rest_do_request($request);
            } else {
                $request = new \WP_REST_Request('POST', '/podlove/v1/shownotes');
                $request->set_query_params([
                    'episode_id' => $episode->id,
                    'data'       => [
                        'title' => $element->textContent,
                    ],
                    'title'      => $element->textContent,
                    'type'       => 'topic',
                ]);
                rest_do_request($request);
            }
        }

        $response = rest_ensure_response(["message" => "ok"]);

        return $response;
    }

    public function import_osf($request)
    {
        $post_id = $request['post_id'];

        if (!function_exists('osf_parser')) {
            return new \WP_Error(
                'podlove_rest_osf_no_function',
                'function "osf_parser" is not available',
                ['status' => 400]
            );
        }

        $shownotes = get_post_meta($post_id, "_shownotes", true);

        $tags = explode(' ', 'chapter section spoiler topic embed video audio image shopping glossary source app title quote link podcast news');
        $data = [
            'amazon'       => '',
            'thomann'      => '',
            'tradedoubler' => '',
            'fullmode'     => 'true', // sic
            'tagsmode'     => 1,
            'tags'         => $tags,
        ];
        $parsed = osf_parser($shownotes, $data);

        $links = $parsed['export'][0]['subitems'];

        if (!is_array($links)) {
            return new \WP_Error(
                'podlove_rest_osf_no_links',
                'there are no osf shownotes or links in them',
                ['status' => 400]
            );
        }

        $links = array_map(function ($link) {
            if (!$link['orig'] || !$link['urls'] || !count($link['urls'])) {
                return null;
            }

            return [
                'title' => $link['orig'],
                'url'   => $link['urls'][0],
            ];
        }, $links);
        $links = array_filter($links);

        if (!$episode = \Podlove\Model\Episode::find_or_create_by_post_id($post_id)) {
            return new \WP_Error(
                'podlove_rest_osf_no_episode',
                'episode cannot be found',
                ['status' => 400]
            );
        }

        foreach ($links as $link) {
            $request = new \WP_REST_Request('POST', '/podlove/v1/shownotes');
            $request->set_query_params([
                'episode_id'   => $episode->id,
                'original_url' => $link['url'],
                'data'         => [
                    'title' => $link['title'],
                ],
                'type'         => 'link',
            ]);
            rest_do_request($request);
        }

        $response = rest_ensure_response(["message" => "ok"]);

        return $response;
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
            $entry = apply_filters('podlove_shownotes_entry', $entry);
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

        if ($request["type"] == "link") {
            return $this->create_link_item($request, $episode);
        } elseif ($request["type"] == "topic") {
            return $this->create_topic_item($request, $episode);
        }
    }

    private function create_link_item($request, $episode)
    {
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

        if (isset($request["data"]) && is_array($request["data"])) {
            // additional data from Slacknotes Import
            // lower precedence than the other data

            if (isset($request["data"]["title"])) {
                $entry->title = $request["data"]["title"];
            }

            if (isset($request["data"]["source"])) {
                $entry->site_name = $request["data"]["source"];
            }

            if (isset($request["data"]["unix_date"])) {
                $entry->created_at = intval($request["data"]["unix_date"]) / 1000;
            }
        }

        foreach (Entry::property_names() as $property) {
            if (isset($request[$property]) && $request[$property]) {
                $entry->$property = $request[$property];
            }
        }
        // fixme: there is probably a race condition here when adding multiple episodes at once
        $entry->position   = Entry::get_new_position_for_episode($episode->id);
        $entry->episode_id = $episode->id;

        if (!$entry->type) {
            $entry->type = "link";
        }

        if (!$entry->save()) {
            return new \WP_Error(
                'podlove_rest_create_failed',
                'error when creating entry',
                ['status' => 400]
            );
        }

        $entry = apply_filters('podlove_shownotes_entry', $entry);

        $response = rest_ensure_response($entry->to_array());
        $response->set_status(201);

        $url = sprintf('%s/%s/%d', self::api_namespace, self::api_base, $entry->id);
        $response->header('Location', rest_url($url));

        return $response;
    }

    private function create_topic_item($request, $episode)
    {
        if (!$request["title"]) {
            return new \WP_Error(
                'podlove_rest_missing_title',
                'title is required for type "topic"',
                ['status' => 400]
            );
        }

        $entry = new Entry;

        foreach (Entry::property_names() as $property) {
            if (isset($request[$property]) && $request[$property]) {
                $entry->$property = $request[$property];
            }
        }
        // fixme: there is probably a race condition here when adding multiple episodes at once
        $entry->position   = Entry::get_new_position_for_episode($episode->id);
        $entry->episode_id = $episode->id;

        if (!$entry->type) {
            $entry->type = "topic";
        }

        if (!$entry->save()) {
            return new \WP_Error(
                'podlove_rest_create_failed',
                'error when creating entry',
                ['status' => 400]
            );
        }

        $entry = apply_filters('podlove_shownotes_entry', $entry);

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

        $entry = apply_filters('podlove_shownotes_entry', $entry);

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

        $unfurl_endpoint = "http://unfurl.eric.co.de/unfurl";
        $curl            = new Curl;
        $curl->request(add_query_arg("url", urlencode($url), $unfurl_endpoint), [
            'headers' => ['Content-type' => 'application/json'],
            'timeout' => 20,
        ]);

        $response = $curl->get_response();

        if (!$curl->isSuccessful()) {

            $entry->state = 'failed';
            $entry->save();

            $body   = json_decode($response['body'], true);
            $reason = $body['error']['reason'] ?? 'unknown reason';

            return new \WP_Error(
                'podlove_rest_unfurl_failed',
                "error when unfurling entry (" . print_r($reason, true) . ")",
                ['status' => 404]
            );
        }

        $data = json_decode(\Podlove\maybe_encode_emoji($response['body']), true);

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
        $entry->prepare_icon();
        $success = $entry->save();

        if (!$success) {
            return new \WP_Error(
                'podlove_rest_unfurl_save_failed',
                'error when saving unfurled entry',
                [
                    'status'    => 404,
                    'locations' => $data['locations'],
                ]
            );
        }

        $entry = apply_filters('podlove_shownotes_entry', $entry);

        $response = rest_ensure_response($entry->to_array());

        return $response;
    }

    public function update_item($request)
    {
        $entry = Entry::find_by_id($request['id']);
        if (is_wp_error($entry)) {
            return $entry;
        }

        if (isset($request['title'])) {
            $entry->title = $request['title'];
        }

        if (isset($request['url'])) {
            $entry->url = $request['url'];
        }

        if (isset($request['description'])) {
            $entry->description = $request['description'];
        }

        if (isset($request['position'])) {
            $entry->position = $request['position'];
        }

        $entry->save();

        $entry = apply_filters('podlove_shownotes_entry', $entry);

        $response = rest_ensure_response($entry->to_array());

        return $response;
    }
}
