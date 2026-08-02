<?php

namespace Podlove\ImageCache;

class SourcePolicy
{
    private const GRAVATAR_CANONICAL_HOST = '0.gravatar.com';

    private const GRAVATAR_HOSTS = [
        'gravatar.com',
        'www.gravatar.com',
        'secure.gravatar.com',
        '0.gravatar.com',
        '1.gravatar.com',
        '2.gravatar.com',
    ];

    public static function is_gravatar_avatar($url)
    {
        $parts = self::gravatar_url_parts($url);

        return null !== $parts && 1 === preg_match('#^/avatar(?:/|$)#i', $parts['path']);
    }

    public static function allows_download($url)
    {
        if (self::is_blocked_source($url)) {
            return false;
        }

        // Block every URL on an image-serving Gravatar host. In particular, do
        // not let an invalid avatar path fall through to the local image cache.
        return null === self::gravatar_url_parts($url);
    }

    public static function direct_url($url, $width = null, $height = null)
    {
        if (self::is_blocked_source($url)) {
            return null;
        }

        $parts = self::gravatar_url_parts($url);
        if (null === $parts) {
            return $url;
        }

        if (!self::is_gravatar_avatar($url)
            || 1 !== preg_match('#^/avatar/([a-f0-9]{32}|[a-f0-9]{64})(?:\.jpg)?/?$#i', $parts['path'], $matches)) {
            return null;
        }

        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $size = max((int) $width, (int) $height);
        if ($size <= 0) {
            $query_size = self::scalar_query_value($query, 's', 'size');
            $size = is_string($query_size) && ctype_digit($query_size) ? (int) $query_size : 0;
        }

        $parameters = [];
        if ($size > 0) {
            $parameters['s'] = min(2048, max(1, $size));
        }

        $default = strtolower((string) self::scalar_query_value($query, 'd', 'default'));
        if ('mm' === $default) {
            $default = 'mp';
        }
        if (in_array($default, ['404', 'mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank', 'initials', 'color'], true)) {
            $parameters['d'] = $default;
        }

        $rating = strtolower((string) self::scalar_query_value($query, 'r', 'rating'));
        if (in_array($rating, ['g', 'pg', 'r', 'x'], true)) {
            $parameters['r'] = $rating;
        }

        $canonical_url = 'https://'.self::GRAVATAR_CANONICAL_HOST.'/avatar/'.strtolower($matches[1]);

        return empty($parameters) ? $canonical_url : add_query_arg($parameters, $canonical_url);
    }

    public static function is_blocked_source($url)
    {
        if (!is_string($url)) {
            return false;
        }

        $url = trim($url);

        return self::has_unsafe_url_characters($url) || self::is_data_uri($url) || self::has_blocked_path($url);
    }

    private static function is_data_uri($url)
    {
        return 1 === preg_match('/\Adata:/i', $url);
    }

    private static function has_unsafe_url_characters($url)
    {
        // Browsers remove tabs and newlines anywhere in a URL and interpret
        // backslashes as path separators for HTTP(S). Reject these ambiguous
        // forms instead of applying the source policy to a different URL than
        // the browser eventually requests.
        return false !== strpos($url, '\\') || 1 === preg_match('/[\x00-\x20\x7f]/', $url);
    }

    private static function has_blocked_path($url)
    {
        if ('' === $url) {
            return false;
        }

        $parts = wp_parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $path = rawurldecode((string) ($parts['path'] ?? ''));
        if (false !== strpos($path, '\\') || 1 === preg_match('/[\x00-\x1f\x7f]/', $path)) {
            return true;
        }

        $segments = explode('/', $path);
        if (in_array('..', $segments, true)) {
            return true;
        }

        $path = self::normalize_path($segments);

        return 1 === preg_match('/\.svg\z/i', $path);
    }

    private static function normalize_path(array $path_segments)
    {
        $segments = [];

        foreach ($path_segments as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private static function gravatar_url_parts($url)
    {
        if (!is_string($url) || '' === $url) {
            return null;
        }

        $parts = wp_parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = rtrim(strtolower((string) ($parts['host'] ?? '')), '.');
        if (!in_array($scheme, ['http', 'https'], true) || !in_array($host, self::GRAVATAR_HOSTS, true)) {
            return null;
        }

        $parts['host'] = $host;
        $parts['path'] = (string) ($parts['path'] ?? '');

        return $parts;
    }

    private static function scalar_query_value(array $query, $short_name, $long_name)
    {
        $value = $query[$short_name] ?? $query[$long_name] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
