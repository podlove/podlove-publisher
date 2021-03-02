<?php

namespace Podlove\Modules\Shownotes;

use Podlove\Http\Curl;
use Podlove\Model\Episode;
use Podlove\Modules\Shownotes\Model\Entry;

class REST_API
{
    const api_namespace = 'podlove/v1';
    const api_base = 'shownotes';

    public function register_routes()
    {
        register_rest_route(self::api_namespace, self::api_base, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'permission_check'],
                'args' => [
                    'episode_id' => [
                        'description' => 'Limit result set by episode.',
                        'type' => 'integer',
                    ],
                ],
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
        register_rest_route(self::api_namespace, self::api_base.'/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the object.'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
        register_rest_route(self::api_namespace, self::api_base.'/(?P<id>[\d]+)/unfurl', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the object.'),
                    'type' => 'integer',
                ],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'unfurl_item'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
        register_rest_route(self::api_namespace, self::api_base.'/osf', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'import_osf'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
        register_rest_route(self::api_namespace, self::api_base.'/html', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'import_html'],
                'permission_callback' => [$this, 'permission_check'],
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

        $html = $request['html'] ?? get_the_content(null, false, $post_id);

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;

        // load html and ensure utf-8
        // @see php DOMDocument::loadHTML doc comments
        $valid = $dom->loadHTML('<?xml encoding="UTF-8">'.$html);

        foreach ($dom->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $dom->removeChild($item);
            }
        }

        $dom->encoding = 'UTF-8';

        if (!$valid) {
            return new \WP_Error(
                'podlove_rest_html_unreadable',
                'html could not be parsed',
                ['status' => 400]
            );
        }

        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//a | //h1 | //h2 | //h3 | //h4 | //h5 | //h6') as $element) {
            if ($element->tagName == 'a') {
                $request = new \WP_REST_Request('POST', '/podlove/v1/shownotes');
                $request->set_query_params([
                    'episode_id' => $episode->id,
                    'original_url' => $element->getAttribute('href'),
                    'data' => [
                        'title' => $element->textContent,
                    ],
                    'type' => 'link',
                ]);
                rest_do_request($request);
            } else {
                $request = new \WP_REST_Request('POST', '/podlove/v1/shownotes');
                $request->set_query_params([
                    'episode_id' => $episode->id,
                    'data' => [
                        'title' => $element->textContent,
                    ],
                    'title' => $element->textContent,
                    'type' => 'topic',
                ]);
                rest_do_request($request);
            }
        }

        return rest_ensure_response(['message' => 'ok']);
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

        $shownotes = get_post_meta($post_id, '_shownotes', true);

        $tags = explode(' ', 'chapter section spoiler topic embed video audio image shopping glossary source app title quote link podcast news');
        $data = [
            'amazon' => '',
            'thomann' => '',
            'tradedoubler' => '',
            'fullmode' => 'true', // sic
            'tagsmode' => 1,
            'tags' => $tags,
        ];
        $parsed = osf_parser($shownotes, $data);

        $links = [];

        foreach ($parsed['export'] as $group) {
            if ($group['chapter']) {
                $links[] = [
                    'type' => 'topic',
                    'title' => $group['orig']
                ];
            }
            foreach ($group['subitems'] as $link) {
                $link['type'] = 'link';
                $links[] = $link;
            }
        }

        if (!is_array($links)) {
            return new \WP_Error(
                'podlove_rest_osf_no_links',
                'there are no osf shownotes or links in them',
                ['status' => 400]
            );
        }

        $links = array_map(function ($link) {
            if ($link['type'] == 'link') {
                if (!$link['orig'] || !$link['urls'] || !count($link['urls'])) {
                    return null;
                }

                return [
                    'type' => 'link',
                    'title' => $link['orig'],
                    'url' => $link['urls'][0],
                ];
            }

            return $link;
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
            if ($link['type'] == 'link') {
                $request->set_query_params([
                    'episode_id' => $episode->id,
                    'original_url' => $link['url'],
                    'data' => [
                        'title' => $link['title'],
                    ],
                    'type' => 'link',
                ]);
            } else {
                $request->set_query_params([
                    'episode_id' => $episode->id,
                    'data' => [
                        'title' => $link['title'],
                    ],
                    'title' => $link['title'],
                    'type' => 'topic',
                ]);
            }
            rest_do_request($request);
        }

        return rest_ensure_response(['message' => 'ok']);
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

        return rest_ensure_response($entries);
    }

    public function create_item($request)
    {
        if (!$request['episode_id']) {
            return new \WP_Error(
                'podlove_rest_missing_episode_id',
                'episode_id is required',
                ['status' => 400]
            );
        }

        $episode = Episode::find_by_id($request['episode_id']);

        if (!$episode) {
            return new \WP_Error(
                'podlove_rest_episode_not_found',
                'episode does not exist',
                ['status' => 400]
            );
        }

        if ($request['type'] == 'link') {
            return $this->create_link_item($request, $episode);
        }
        if ($request['type'] == 'topic') {
            return $this->create_topic_item($request, $episode);
        }
    }

    public function get_item($request)
    {
        $entry = Entry::find_by_id($request['id']);

        if (is_wp_error($entry)) {
            return $entry;
        }

        $entry = apply_filters('podlove_shownotes_entry', $entry);

        return rest_ensure_response($entry->to_array());
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

        $unfurl_endpoint = 'https://plus.podlove.org/api/unfurl';
        $curl = new Curl();
        $curl->request(add_query_arg('url', urlencode($url), $unfurl_endpoint), [
            'headers' => ['Content-type' => 'application/json'],
            'timeout' => 20,
        ]);

        $response = $curl->get_response();

        if (!$curl->isSuccessful()) {
            $entry->state = 'failed';
            $entry->save();

            $body = json_decode($response['body'], true);
            $reason = $body['error']['reason'] ?? 'unknown reason';

            return new \WP_Error(
                'podlove_rest_unfurl_failed',
                'error when unfurling entry ('.print_r($reason, true).')',
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
        $entry->state = 'fetched';
        $entry->url = $data['url'];
        $entry->icon = $data['icon']['url'];
        $entry->image = $data['image'];

        // todo: should probably do this in an async job
        $attachment_id = 0;

        if ($data['image']) {
            $attachment_id = \Podlove\download_external_image_to_media($data['image'], explode('?', basename($data['image']))[0]);
        }

        if (!$attachment_id && $data['screenshot_url']) {
            if (\Podlove\Modules\Base::is_active('plus')) {
                $plus = \Podlove\Modules\Plus\Plus::instance();
                $curl_args = [
                    'headers' => [
                        'Authorization' => 'Bearer '.$plus->get_module_option('plus_api_token')
                    ]
                ];
                $attachment_id = \Podlove\download_external_image_to_media($data['screenshot_url'], 'screenshot.jpg', $curl_args);
            }
        }

        if ($attachment_id && !\is_wp_error($attachment_id)) {
            $attachment_url = \wp_get_attachment_url($attachment_id);
            $entry->image = $attachment_url;
        }

        if (!$entry->title) {
            $entry->title = $data['title'];
        }

        if (!$entry->description) {
            $entry->description = $data['description'];
        }

        if (!$entry->site_name) {
            $entry->site_name = $data['site_name'];
        }

        if (!$entry->site_url) {
            $entry->site_url = $data['site_url'];
        }

        $entry->prepare_icon();
        $success = $entry->save();

        if ($success === false) {
            return new \WP_Error(
                'podlove_rest_unfurl_save_failed',
                'error when saving unfurled entry',
                [
                    'status' => 404,
                    'locations' => $data['locations'],
                ]
            );
        }

        $entry = apply_filters('podlove_shownotes_entry', $entry);

        return rest_ensure_response($entry->to_array());
    }

    public function update_item($request)
    {
        $entry = Entry::find_by_id($request['id']);
        if (is_wp_error($entry)) {
            return $entry;
        }

        if (isset($request['original_url'])) {
            $entry->original_url = $request['original_url'];
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

        if (isset($request['hidden'])) {
            $entry->hidden = (int) $request['hidden'];
        }

        $entry->save();

        $entry = apply_filters('podlove_shownotes_entry', $entry);

        return rest_ensure_response($entry->to_array());
    }

    public function permission_check()
    {
        if (!current_user_can('edit_posts')) {
            return new \WP_Error('rest_forbidden', 'sorry, you do not have permissions to use this REST API endpoint', ['status' => 401]);
        }

        return true;
    }

    private function create_link_item($request, $episode)
    {
        $original_url = esc_sql($request['original_url']);
        $episode_id = (int) $episode->id;

        if (Entry::find_one_by_where("episode_id = {$episode_id} AND original_url = '{$original_url}'")) {
            return new \WP_Error(
                'podlove_rest_duplicate_entry',
                'a shownotes entry for this URL exists already',
                ['status' => 400]
            );
        }

        $entry = new Entry();

        if (isset($request['data']) && is_array($request['data'])) {
            // additional data from Slacknotes Import
            // lower precedence than the other data

            if (isset($request['data']['title'])) {
                $entry->title = $request['data']['title'];
            }

            if (isset($request['data']['source'])) {
                $entry->site_name = $request['data']['source'];
            }

            if (isset($request['data']['unix_date'])) {
                $entry->created_at = intval($request['data']['unix_date']) / 1000;
            }

            if (isset($request['data']['orderNumber'])) {
                $entry->position = intval($request['data']['orderNumber']) / 1000;
            }
        }

        foreach (Entry::property_names() as $property) {
            if (isset($request[$property]) && $request[$property]) {
                $entry->{$property} = $request[$property];
            }
        }

        // fixme: there is probably a race condition here when adding multiple episodes at once
        if (!$entry->position) {
            $entry->position = Entry::get_new_position_for_episode($episode->id);
        }
        $entry->episode_id = $episode->id;

        if (!$entry->type) {
            $entry->type = 'link';
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
        if (!$request['title']) {
            return new \WP_Error(
                'podlove_rest_missing_title',
                'title is required for type "topic"',
                ['status' => 400]
            );
        }

        $entry = new Entry();

        foreach (Entry::property_names() as $property) {
            if (isset($request[$property]) && $request[$property]) {
                $entry->{$property} = $request[$property];
            }
        }
        // fixme: there is probably a race condition here when adding multiple episodes at once
        $entry->position = Entry::get_new_position_for_episode($episode->id);
        $entry->episode_id = $episode->id;

        if (!$entry->type) {
            $entry->type = 'topic';
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
}
