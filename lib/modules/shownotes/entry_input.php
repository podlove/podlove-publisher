<?php

namespace Podlove\Modules\Shownotes;

use Podlove\Modules\Shownotes\Model\Entry;

class EntryInput
{
    private const URL_PROTOCOLS = ['http', 'https'];

    public static function create_args()
    {
        return array_merge(
            [
                'episode_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'minimum' => 1,
                ],
                'type' => [
                    'type' => 'string',
                    'required' => true,
                    'enum' => ['link', 'topic'],
                ],
                'data' => [
                    'type' => 'object',
                    'sanitize_callback' => [__CLASS__, 'sanitize_slack_data_argument'],
                ],
            ],
            self::mutable_args(),
            [
                'site_name' => self::text_arg(),
                'site_url' => self::url_arg(),
                'icon' => self::url_arg(),
                'image' => self::url_arg(),
                'created_at' => [
                    'sanitize_callback' => [__CLASS__, 'sanitize_non_negative_integer_argument'],
                ],
            ]
        );
    }

    public static function update_args()
    {
        return self::mutable_args();
    }

    public static function sanitize_text_argument($value)
    {
        if (!is_scalar($value) && null !== $value) {
            return self::invalid_value('text');
        }

        return self::sanitize_text((string) $value);
    }

    public static function sanitize_textarea_argument($value)
    {
        if (!is_scalar($value) && null !== $value) {
            return self::invalid_value('textarea');
        }

        return self::sanitize_textarea((string) $value);
    }

    public static function sanitize_url_argument($value)
    {
        return self::sanitize_url($value);
    }

    public static function sanitize_number_argument($value)
    {
        if (!is_numeric($value)) {
            return self::invalid_value('number');
        }

        $number = (float) $value;
        if (!is_finite($number)) {
            return self::invalid_value('number');
        }

        return $number;
    }

    public static function sanitize_non_negative_integer_argument($value)
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) {
            return self::invalid_value('non-negative integer');
        }

        return (int) $value;
    }

    public static function sanitize_boolean_argument($value)
    {
        if (is_bool($value)) {
            return (int) $value;
        }

        if ($value === 0 || $value === 1 || $value === '0' || $value === '1') {
            return (int) $value;
        }

        return self::invalid_value('boolean');
    }

    public static function sanitize_slack_data_argument($value)
    {
        if (!is_array($value)) {
            return self::invalid_value('Slacknotes data');
        }

        $data = [];

        if (array_key_exists('title', $value)) {
            $data['title'] = self::sanitize_text_argument($value['title']);
        }

        if (array_key_exists('source', $value)) {
            $data['source'] = self::sanitize_text_argument($value['source']);
        }

        foreach (['unix_date', 'orderNumber'] as $field) {
            if (!array_key_exists($field, $value)) {
                continue;
            }

            $number = self::sanitize_number_argument($value[$field]);
            if (is_wp_error($number) || $number < 0) {
                return self::invalid_value('non-negative number');
            }

            $data[$field] = $number;
        }

        foreach ($data as $field_value) {
            if (is_wp_error($field_value)) {
                return $field_value;
            }
        }

        return $data;
    }

    public static function normalize_entry_for_read(Entry $entry)
    {
        $entry = clone $entry;
        $entry->title = self::sanitize_text((string) ($entry->title ?? ''));
        $entry->description = self::sanitize_textarea((string) ($entry->description ?? ''));
        $entry->site_name = self::sanitize_text((string) ($entry->site_name ?? ''));

        foreach (['original_url', 'url', 'site_url', 'icon', 'image', 'affiliate_url'] as $field) {
            if (null !== $entry->{$field}) {
                $entry->{$field} = self::safe_stored_url($entry->{$field});
            }
        }

        $unfurl_data = $entry->unfurl_data_array();
        if (is_array($unfurl_data)) {
            $normalized_unfurl_data = self::normalize_unfurl_data($unfurl_data);
            $entry->unfurl_data = is_wp_error($normalized_unfurl_data) ? null : $normalized_unfurl_data;
        }

        return $entry;
    }

    public static function normalize_unfurl_data($value)
    {
        if (!is_array($value)) {
            return new \WP_Error(
                'podlove_rest_invalid_unfurl_response',
                'unfurl response must be an object',
                ['status' => 502]
            );
        }

        $site_url = self::safe_stored_url($value['site_url'] ?? '');
        $data = [
            'url' => self::safe_remote_url($value['url'] ?? '', $site_url),
            'title' => self::sanitize_text((string) ($value['title'] ?? '')),
            'description' => self::sanitize_textarea((string) ($value['description'] ?? '')),
            'site_name' => self::sanitize_text((string) ($value['site_name'] ?? '')),
            'site_url' => $site_url,
            'icon' => [
                'url' => self::safe_remote_url(self::nested_value($value, ['icon', 'url']), $site_url),
            ],
            'image' => self::first_safe_remote_url($value['image'] ?? '', $site_url),
            'screenshot_url' => self::safe_remote_url($value['screenshot_url'] ?? '', $site_url),
            'locations' => [],
            'providers' => [
                'misc' => ['icons' => []],
                'open_graph' => [],
                'twitter' => [],
            ],
        ];

        if (isset($value['locations']) && is_array($value['locations'])) {
            $data['locations'] = array_map(function ($location) {
                return self::sanitize_text(is_scalar($location) ? (string) $location : '');
            }, $value['locations']);
        }

        $open_graph_image = self::normalized_image_value(
            self::nested_value($value, ['providers', 'open_graph', 'image']),
            $site_url
        );
        if ($open_graph_image) {
            $data['providers']['open_graph']['image'] = $open_graph_image;
        }

        $twitter_image = self::normalized_image_value(
            self::nested_value($value, ['providers', 'twitter', 'image:src']),
            $site_url
        );
        if ($twitter_image) {
            $data['providers']['twitter']['image:src'] = $twitter_image;
        }

        $misc_icons = self::nested_value($value, ['providers', 'misc', 'icons']);
        if (is_array($misc_icons)) {
            foreach ($misc_icons as $icon) {
                $icon_url = self::safe_remote_url(self::nested_value($icon, ['url']), $site_url);
                if ($icon_url) {
                    $data['providers']['misc']['icons'][] = ['url' => $icon_url];
                }
            }
        }

        return $data;
    }

    private static function mutable_args()
    {
        return [
            'original_url' => self::url_arg(),
            'url' => self::url_arg(),
            'title' => self::text_arg(),
            'description' => [
                'type' => 'string',
                'sanitize_callback' => [__CLASS__, 'sanitize_textarea_argument'],
            ],
            'position' => [
                'sanitize_callback' => [__CLASS__, 'sanitize_number_argument'],
            ],
            'hidden' => [
                'sanitize_callback' => [__CLASS__, 'sanitize_boolean_argument'],
            ],
        ];
    }

    private static function text_arg()
    {
        return [
            'type' => 'string',
            'sanitize_callback' => [__CLASS__, 'sanitize_text_argument'],
        ];
    }

    private static function url_arg()
    {
        return [
            'type' => 'string',
            'sanitize_callback' => [__CLASS__, 'sanitize_url_argument'],
        ];
    }

    private static function sanitize_text($value)
    {
        return sanitize_text_field(self::decode_entities($value));
    }

    private static function sanitize_textarea($value)
    {
        return sanitize_textarea_field(self::decode_entities($value));
    }

    private static function sanitize_url($value)
    {
        if (!is_scalar($value) && null !== $value) {
            return self::invalid_value('URL');
        }

        $value = trim(self::decode_entities((string) $value));
        if ($value === '') {
            return '';
        }

        $parts = wp_parse_url($value);
        if (
            !is_array($parts)
            || empty($parts['scheme'])
            || empty($parts['host'])
            || !in_array(strtolower($parts['scheme']), self::URL_PROTOCOLS, true)
        ) {
            return self::invalid_value('HTTP(S) URL');
        }

        $url = esc_url_raw($value, self::URL_PROTOCOLS);

        return $url === '' ? self::invalid_value('HTTP(S) URL') : $url;
    }

    private static function safe_stored_url($value)
    {
        $url = self::sanitize_url($value);

        return is_wp_error($url) ? '' : $url;
    }

    private static function safe_remote_url($value, $base_url = '')
    {
        if (!is_scalar($value) && null !== $value) {
            return '';
        }

        $value = trim(self::decode_entities((string) $value));
        if (str_starts_with($value, '//')) {
            return '';
        }

        if ($value !== '' && str_starts_with($value, '/')) {
            $value = self::absolute_url($value, $base_url);
        }

        return self::safe_stored_url($value);
    }

    private static function first_safe_remote_url($value, $base_url)
    {
        if (!is_array($value)) {
            return self::safe_remote_url($value, $base_url);
        }

        foreach ($value as $candidate) {
            $url = self::safe_remote_url($candidate, $base_url);
            if ($url) {
                return $url;
            }
        }

        return '';
    }

    private static function normalized_image_value($value, $base_url)
    {
        if (!is_array($value)) {
            return self::safe_remote_url($value, $base_url);
        }

        $urls = [];
        foreach ($value as $candidate) {
            $url = self::safe_remote_url($candidate, $base_url);
            if ($url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    private static function absolute_url($path, $base_url)
    {
        $parts = wp_parse_url($base_url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $authority = $parts['scheme'].'://'.$parts['host'];
        if (!empty($parts['port'])) {
            $authority .= ':'.(int) $parts['port'];
        }

        return $authority.$path;
    }

    private static function nested_value($value, array $path)
    {
        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
    }

    private static function decode_entities($value)
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function invalid_value($expected)
    {
        return new \WP_Error(
            'podlove_rest_invalid_shownotes_value',
            sprintf('shownotes value must be a valid %s', $expected),
            ['status' => 400]
        );
    }
}
