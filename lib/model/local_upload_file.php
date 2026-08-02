<?php

namespace Podlove\Model;

class LocalUploadFile
{
    public static function path_for_url($url)
    {
        $upload_dir = self::upload_dir();
        if (null === $upload_dir) {
            return null;
        }

        return LocalFile::path_for_url($url, $upload_dir['baseurl'], $upload_dir['basedir']);
    }

    public static function existing_path_for_url($url)
    {
        $upload_dir = self::upload_dir();
        if (null === $upload_dir) {
            return null;
        }

        return LocalFile::existing_path_for_url($url, $upload_dir['baseurl'], $upload_dir['basedir']);
    }

    private static function upload_dir()
    {
        $upload_dir = wp_upload_dir();

        if (empty($upload_dir['baseurl']) || empty($upload_dir['basedir'])) {
            return null;
        }

        return $upload_dir;
    }
}
