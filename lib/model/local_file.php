<?php

namespace Podlove\Model;

class LocalFile
{
    public static function path_for_url($url, $base_url, $base_dir)
    {
        if (!is_string($url) || '' === $url
            || !is_string($base_url) || '' === $base_url
            || !is_string($base_dir) || '' === $base_dir) {
            return null;
        }

        $url_without_query = strtok($url, '?#');
        $base_url = untrailingslashit($base_url);

        if (!is_string($url_without_query) || 0 !== strpos($url_without_query, $base_url.'/')) {
            return null;
        }

        $relative_path = rawurldecode(substr($url_without_query, strlen($base_url) + 1));
        $relative_path = wp_normalize_path($relative_path);

        if ('' === $relative_path
            || 1 === preg_match('/[\x00-\x1f\x7f]/', $relative_path)
            || in_array('..', explode('/', $relative_path), true)) {
            return null;
        }

        $base_dir = wp_normalize_path(trailingslashit($base_dir));
        $path = wp_normalize_path($base_dir.$relative_path);

        if (0 !== strpos($path, $base_dir)) {
            return null;
        }

        return $path;
    }

    public static function existing_path_for_url($url, $base_url, $base_dir)
    {
        $path = self::path_for_url($url, $base_url, $base_dir);
        if (null === $path) {
            return null;
        }

        $real_base_dir = realpath($base_dir);
        $real_path = realpath($path);
        if (false === $real_base_dir || false === $real_path || !is_file($real_path)) {
            return null;
        }

        $real_base_dir = wp_normalize_path(trailingslashit($real_base_dir));
        $real_path = wp_normalize_path($real_path);

        if (0 !== strpos($real_path, $real_base_dir)) {
            return null;
        }

        return $real_path;
    }
}
