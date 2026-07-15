<?php

namespace Podlove\ImageCache;

class Request
{
    public const SIGNATURE_VERSION = 'podlove-image-cache-v1';
    public const EMPTY_FILE_NAME_TOKEN = '-';
    public const MAX_SOURCE_URL_LENGTH = 4096;

    private $encoded_source_url;
    private $source_url;
    private $width;
    private $height;
    private $crop;
    private $file_name;
    private $encoded_file_name;
    private $signature;

    private function __construct(
        $encoded_source_url,
        $source_url,
        $width,
        $height,
        $crop,
        $file_name,
        $encoded_file_name,
        $signature = ''
    ) {
        $this->encoded_source_url = $encoded_source_url;
        $this->source_url = $source_url;
        $this->width = $width;
        $this->height = $height;
        $this->crop = $crop;
        $this->file_name = $file_name;
        $this->encoded_file_name = $encoded_file_name;
        $this->signature = $signature;
    }

    public static function from_values($source_url, $width, $height, $crop, $file_name)
    {
        $source_url = trim((string) $source_url);
        $encoded_source_url = bin2hex($source_url);
        $file_name = self::sanitize_file_name($file_name);
        $encoded_file_name = self::encode_file_name($file_name);

        return self::create(
            $encoded_source_url,
            $source_url,
            (string) (int) $width,
            (string) (int) $height,
            $crop ? '1' : '0',
            $file_name,
            $encoded_file_name
        );
    }

    public static function from_query_vars(
        $encoded_source_url,
        $width,
        $height,
        $crop,
        $encoded_file_name,
        $signature = ''
    ) {
        foreach ([$encoded_source_url, $width, $height, $crop, $encoded_file_name, $signature] as $value) {
            if (!is_scalar($value) && null !== $value) {
                return new \WP_Error('podlove_image_cache_invalid_request', __('Invalid image cache request.'));
            }
        }

        $encoded_source_url = (string) $encoded_source_url;
        $encoded_file_name = (string) $encoded_file_name;
        $signature = (string) $signature;

        if ('' === $encoded_source_url
            || strlen($encoded_source_url) > self::MAX_SOURCE_URL_LENGTH * 2
            || 0 !== strlen($encoded_source_url) % 2
            || !preg_match('/\A[0-9a-f]+\z/', $encoded_source_url)) {
            return new \WP_Error('podlove_image_cache_invalid_source', __('Invalid image cache source.'));
        }

        $source_url = hex2bin($encoded_source_url);
        if (false === $source_url) {
            return new \WP_Error('podlove_image_cache_invalid_source', __('Invalid image cache source.'));
        }

        if (self::EMPTY_FILE_NAME_TOKEN === $encoded_file_name || '' === $encoded_file_name) {
            $file_name = '';
        } else {
            $file_name = rawurldecode($encoded_file_name);
            if (self::encode_file_name($file_name) !== $encoded_file_name) {
                return new \WP_Error('podlove_image_cache_invalid_filename', __('Invalid image cache filename.'));
            }
        }

        $sanitized_file_name = self::sanitize_file_name($file_name);
        if ($sanitized_file_name !== $file_name) {
            return new \WP_Error('podlove_image_cache_invalid_filename', __('Invalid image cache filename.'));
        }

        if ('' !== $signature && !preg_match('/\A[0-9a-f]{32}\z/', $signature)) {
            return new \WP_Error('podlove_image_cache_invalid_signature', __('Invalid image cache signature.'));
        }

        return self::create(
            $encoded_source_url,
            $source_url,
            $width,
            $height,
            $crop,
            $file_name,
            $encoded_file_name,
            $signature
        );
    }

    public static function sanitize_file_name($file_name)
    {
        $file_name = sanitize_title((string) $file_name);

        if (function_exists('iconv')) {
            $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $file_name);
            if (false !== $transliterated) {
                $file_name = $transliterated;
            }
        }

        return preg_replace('~[^-a-z0-9_]+~', '', $file_name);
    }

    public static function max_dimension()
    {
        return max(1, (int) apply_filters('podlove_image_cache_max_dimension', 4096));
    }

    public static function max_output_pixels()
    {
        return max(1, (int) apply_filters('podlove_image_cache_max_output_pixels', 16_000_000));
    }

    public static function max_source_pixels()
    {
        return max(1, (int) apply_filters('podlove_image_cache_max_source_pixels', 40_000_000));
    }

    public static function max_source_bytes()
    {
        return max(1, (int) apply_filters('podlove_image_cache_max_source_bytes', 20 * MB_IN_BYTES));
    }

    public static function download_timeout()
    {
        return max(1, (int) apply_filters('podlove_image_cache_download_timeout', 30));
    }

    public static function redirect_limit()
    {
        return max(0, (int) apply_filters('podlove_image_cache_redirect_limit', 3));
    }

    public function signature()
    {
        return substr(hash_hmac('sha256', $this->signature_payload(), wp_salt('auth')), 0, 32);
    }

    public function has_signature()
    {
        return '' !== $this->signature;
    }

    public function has_valid_signature()
    {
        return $this->has_signature() && hash_equals($this->signature(), $this->signature);
    }

    public function signature_payload()
    {
        return implode("\n", [
            self::SIGNATURE_VERSION,
            (string) get_current_blog_id(),
            $this->encoded_source_url,
            (string) $this->width,
            (string) $this->height,
            (string) $this->crop,
            $this->encoded_file_name,
        ]);
    }

    public function source_url()
    {
        return $this->source_url;
    }

    public function encoded_source_url()
    {
        return $this->encoded_source_url;
    }

    public function width()
    {
        return $this->width;
    }

    public function height()
    {
        return $this->height;
    }

    public function crop()
    {
        return (bool) $this->crop;
    }

    public function crop_flag()
    {
        return $this->crop;
    }

    public function file_name()
    {
        return $this->file_name;
    }

    public function encoded_file_name()
    {
        return $this->encoded_file_name;
    }

    public function source_key()
    {
        return hash('sha256', implode("\n", ['source', $this->source_url, $this->file_name]));
    }

    public function rendition_key($width, $height)
    {
        return hash('sha256', implode("\n", [
            'rendition',
            $this->source_key(),
            (string) $width,
            (string) $height,
            (string) $this->crop,
        ]));
    }

    public function source_dimensions_are_allowed($width, $height)
    {
        $width = (int) $width;
        $height = (int) $height;

        return $width > 0 && $height > 0 && $width * $height <= self::max_source_pixels();
    }

    public function output_dimensions_are_allowed($source_width, $source_height, $width, $height)
    {
        $source_width = (int) $source_width;
        $source_height = (int) $source_height;
        $width = (int) $width;
        $height = (int) $height;

        if ($this->crop && $width && $height) {
            return $width * $height <= self::max_output_pixels();
        }

        $scale = 1.0;
        if ($width) {
            $scale = min($scale, $width / $source_width);
        }
        if ($height) {
            $scale = min($scale, $height / $source_height);
        }

        $output_width = max(1, (int) floor($source_width * $scale));
        $output_height = max(1, (int) floor($source_height * $scale));

        return $output_width * $output_height <= self::max_output_pixels();
    }

    private static function create(
        $encoded_source_url,
        $source_url,
        $width,
        $height,
        $crop,
        $file_name,
        $encoded_file_name,
        $signature = ''
    ) {
        if ('' === $source_url || strlen($source_url) > self::MAX_SOURCE_URL_LENGTH) {
            return new \WP_Error('podlove_image_cache_invalid_source', __('Invalid image cache source.'));
        }

        if (!self::is_canonical_integer($width)
            || !self::is_canonical_integer($height)
            || !in_array((string) $crop, ['0', '1'], true)) {
            return new \WP_Error('podlove_image_cache_invalid_dimensions', __('Invalid image cache dimensions.'));
        }

        $width = (int) $width;
        $height = (int) $height;
        $crop = (int) $crop;

        if ((!$width && !$height)
            || $width > self::max_dimension()
            || $height > self::max_dimension()
            || ($width && $height && $width * $height > self::max_output_pixels())) {
            return new \WP_Error('podlove_image_cache_invalid_dimensions', __('Invalid image cache dimensions.'));
        }

        $url = wp_parse_url($source_url);
        if (!is_array($url)) {
            return new \WP_Error('podlove_image_cache_invalid_source', __('Invalid image cache source.'));
        }

        $scheme = strtolower($url['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true) || empty($url['host'])) {
            return new \WP_Error('podlove_image_cache_invalid_source', __('Invalid image cache source.'));
        }

        return new self(
            $encoded_source_url,
            $source_url,
            $width,
            $height,
            $crop,
            $file_name,
            $encoded_file_name,
            $signature
        );
    }

    private static function encode_file_name($file_name)
    {
        return '' === $file_name ? self::EMPTY_FILE_NAME_TOKEN : rawurlencode($file_name);
    }

    private static function is_canonical_integer($value)
    {
        if (!is_scalar($value) || !preg_match('/\A(?:0|[1-9][0-9]*)\z/', (string) $value)) {
            return false;
        }

        return (string) (int) $value === (string) $value;
    }
}
