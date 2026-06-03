<?php

namespace Podlove\Model\Probe;

use Podlove\Model\LocalUploadFile;

class LocalUploadMediaFileProbe
{
    public static function probe($public_url, $verification_url)
    {
        $path = LocalUploadFile::path_for_url($verification_url);

        if (!$path) {
            return null;
        }

        $exists = is_file($path);
        $readable = $exists && is_readable($path);
        $size = null;
        $content_type = null;

        if ($readable) {
            $file_size = filesize($path);
            $size = $file_size === false ? null : (int) $file_size;

            $file_type = wp_check_filetype($path);
            $content_type = $file_type['type'] ?: null;
        }

        return MediaFileProbeResult::local(
            $public_url,
            $verification_url,
            $path,
            $exists,
            $readable,
            $size,
            $content_type
        );
    }
}
