<?php

namespace Podlove\Model;

class LocalUploadFile
{
    public static function path_for_url($url)
    {
        if (!is_string($url) || $url === '') {
            return null;
        }

        $upload_dir = wp_upload_dir();

        if (empty($upload_dir['baseurl']) || empty($upload_dir['basedir'])) {
            return null;
        }

        $url_without_query = strtok($url, '?#');
        $base_url = untrailingslashit($upload_dir['baseurl']);

        if (strpos($url_without_query, $base_url.'/') !== 0) {
            return null;
        }

        $relative_path = rawurldecode(substr($url_without_query, strlen($base_url) + 1));
        $relative_path = wp_normalize_path($relative_path);

        if ($relative_path === '' || in_array('..', explode('/', $relative_path), true)) {
            return null;
        }

        $base_dir = wp_normalize_path(trailingslashit($upload_dir['basedir']));
        $path = wp_normalize_path($base_dir.$relative_path);

        if (strpos($path, $base_dir) !== 0) {
            return null;
        }

        return $path;
    }
}
