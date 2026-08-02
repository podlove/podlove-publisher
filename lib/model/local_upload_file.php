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

        return LocalFile::path_for_url($url, $upload_dir['baseurl'], $upload_dir['basedir']);
    }
}
