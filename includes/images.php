<?php

use Podlove\Cache\HttpHeaderValidator;
use Podlove\ImageCache\GenerationGuard;
use Podlove\ImageCache\Request as ImageCacheRequest;
use Podlove\Log;
use Podlove\Model\Image;
use Symfony\Component\Yaml\Yaml;

// WP Cron: Image cache validation
add_action('wp', function () {
    if (!wp_next_scheduled('podlove_validate_image_cache')) {
        wp_schedule_event(time(), 'daily', 'podlove_validate_image_cache');
    }
});

add_action('podlove_validate_image_cache', 'podlove_validate_image_cache');
add_action('podlove_refetch_cached_image', 'podlove_refetch_cached_image', 10, 2);

function podlove_validate_image_cache()
{
    set_time_limit(5 * MINUTE_IN_SECONDS);

    $start_time = hrtime(true);
    $cache_files = glob(trailingslashit(Image::cache_dir()).'*'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'cache.yml');
    foreach ($cache_files as $cache_file) {
        $cache = Yaml::parse(file_get_contents($cache_file));

        if (!isset($cache['etag'])) {
            $cache['etag'] = null;
        }

        if (!isset($cache['last-modified'])) {
            $cache['last-modified'] = null;
        }

        $validator = new HttpHeaderValidator($cache['source'], $cache['etag'], $cache['last-modified']);
        $validator->validate();
        if ($validator->hasChanged()) {
            wp_schedule_single_event(time(), 'podlove_refetch_cached_image', [$cache['source'], $cache['filename']]);
        }
    }

    $stop_time = hrtime(true);
    $duration = ($stop_time - $start_time) / 1e+6;
    $duration_string = round($duration).'ms';
    \Podlove\Log::get()->addInfo(sprintf('Finished validating %d images in %s', count($cache_files), $duration_string));
}

function podlove_refetch_cached_image($url, $filename)
{
    (new Image($url, $filename))->redownload_source();
}

// add routes
add_action('init', function () {
    add_rewrite_rule(
        '^podlove/image/([^/]+)/([0-9]+)/([0-9]+)/([0-9])/([^/]+)/([a-f0-9]{32})/?$',
        'index.php?podlove_image_cache_url=$matches[1]&podlove_width=$matches[2]&podlove_height=$matches[3]&podlove_crop=$matches[4]&podlove_file_name=$matches[5]&podlove_image_cache_signature=$matches[6]',
        'top'
    );
    add_rewrite_rule(
        '^podlove/image/([^/]+)/([0-9]+)/([0-9]+)/([0-9])/([^/]+)/?$',
        'index.php?podlove_image_cache_url=$matches[1]&podlove_width=$matches[2]&podlove_height=$matches[3]&podlove_crop=$matches[4]&podlove_file_name=$matches[5]',
        'top'
    );
}, 10);

add_filter('query_vars', function ($query_vars) {
    $query_vars[] = 'podlove_image_cache_url';
    $query_vars[] = 'podlove_width';
    $query_vars[] = 'podlove_height';
    $query_vars[] = 'podlove_crop';
    $query_vars[] = 'podlove_file_name';
    $query_vars[] = 'podlove_image_cache_signature';

    return $query_vars;
}, 10, 1);

add_action('wp', 'podlove_handle_cache_files');

function podlove_handle_cache_files()
{
    $encoded_source_url = podlove_get_query_var('podlove_image_cache_url');
    if (!$encoded_source_url) {
        return;
    }

    $request = ImageCacheRequest::from_query_vars(
        $encoded_source_url,
        podlove_get_query_var('podlove_width'),
        podlove_get_query_var('podlove_height'),
        podlove_get_query_var('podlove_crop'),
        podlove_get_query_var('podlove_file_name'),
        podlove_get_query_var('podlove_image_cache_signature')
    );

    if (is_wp_error($request)) {
        podlove_image_cache_fail(404);
    }

    // Tell WP Super Cache to not cache download links
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }

    if ($request->has_signature() && !$request->has_valid_signature()) {
        podlove_image_cache_fail(404);
    }

    $file = podlove_resolve_image_cache_file($request, $request->has_valid_signature());
    if (is_wp_error($file)) {
        $status = 'podlove_image_cache_busy' === $file->get_error_code() ? 503 : 404;
        podlove_image_cache_fail($status, 503 === $status ? 5 : null);
    }

    podlove_serve_cached_image($file);
}

/**
 * Resolve an image cache request to an existing local rendition.
 *
 * When $allow_generation is false, this function performs no network requests
 * and creates no cache files. This is the compatibility path for unsigned URLs.
 *
 * @param bool $allow_generation
 *
 * @return string|WP_Error
 */
function podlove_resolve_image_cache_file(ImageCacheRequest $request, $allow_generation)
{
    $image = new Image($request->source_url(), $request->file_name());

    if (!$image->source_exists()) {
        if (!$allow_generation) {
            return new WP_Error('podlove_image_cache_legacy_miss', __('Unsigned image cache miss.'));
        }

        $source_guard = new GenerationGuard($request->source_key());
        if ($source_guard->is_backed_off()) {
            return new WP_Error('podlove_image_cache_backoff', __('Image source is temporarily unavailable.'));
        }

        if (!$source_guard->acquire()) {
            return new WP_Error('podlove_image_cache_busy', __('Image source is currently being generated.'));
        }

        if (!$image->source_exists()) {
            $image->download_source();
        }

        if (!$image->source_exists()) {
            $source_guard->record_failure();
            $source_guard->release();

            return new WP_Error('podlove_image_cache_download_failed', __('Image download failed.'));
        }

        $source_guard->clear_failure();
        $source_guard->release();
    }

    $image_info = @getimagesize($image->original_file());
    if (false === $image_info) {
        return new WP_Error('podlove_image_cache_invalid_source', __('Cached image source is invalid.'));
    }

    list($orig_width, $orig_height) = $image_info;
    if (!$request->source_dimensions_are_allowed($orig_width, $orig_height)) {
        return new WP_Error('podlove_image_cache_source_too_large', __('Cached image source exceeds the pixel limit.'));
    }

    $width = $request->width();
    $height = $request->height();

    // Do not try to enlarge images
    if ($width > $orig_width) {
        $width = $orig_width;
    }

    if ($height > $orig_height) {
        $height = $orig_height;
    }

    if (!$request->output_dimensions_are_allowed($orig_width, $orig_height, $width, $height)) {
        return new WP_Error('podlove_image_cache_output_too_large', __('Requested image exceeds the pixel limit.'));
    }

    $image
        ->setWidth($width)
        ->setHeight($height)
        ->setCrop($request->crop())
    ;

    if (!file_exists($image->resized_file())) {
        if (!$allow_generation) {
            return new WP_Error('podlove_image_cache_legacy_miss', __('Unsigned image rendition does not exist.'));
        }

        $rendition_guard = new GenerationGuard($request->rendition_key($width, $height));
        if ($rendition_guard->is_backed_off()) {
            return new WP_Error('podlove_image_cache_backoff', __('Image rendition is temporarily unavailable.'));
        }

        if (!$rendition_guard->acquire()) {
            return new WP_Error('podlove_image_cache_busy', __('Image rendition is currently being generated.'));
        }

        if (!file_exists($image->resized_file())) {
            $image->generate_resized_copy();
        }

        if (!file_exists($image->resized_file())) {
            $rendition_guard->record_failure();
            $rendition_guard->release();

            return new WP_Error('podlove_image_cache_resize_failed', __('Image resize failed.'));
        }

        $rendition_guard->clear_failure();
        $rendition_guard->release();
    }

    return $image->resized_file();
}

function podlove_serve_cached_image($file)
{
    $cache_root = realpath(Image::cache_dir());
    $real_file = realpath($file);
    if (false === $cache_root || false === $real_file || !is_file($real_file)) {
        podlove_image_cache_fail(404);
    }

    $normalized_cache_root = trailingslashit(wp_normalize_path($cache_root));
    $normalized_real_file = wp_normalize_path($real_file);
    if (0 !== strpos($normalized_real_file, $normalized_cache_root)) {
        podlove_image_cache_fail(404);
    }

    $image_info = @getimagesize($real_file);
    if (false === $image_info) {
        podlove_image_cache_fail(404);
    }

    $mime_type = image_type_to_mime_type($image_info[2]);
    $allowed_mime_types = ['image/jpeg', 'image/gif', 'image/png', 'image/webp', 'image/avif'];
    if (!in_array($mime_type, $allowed_mime_types, true)) {
        Log::get()->error('Unsupported image type for cached image.');
        podlove_image_cache_fail(404);
    }

    $time = filemtime($real_file);
    $etag = '"'.hash_file('sha256', $real_file).'"';
    $last_modified = gmdate('D, d M Y H:i:s \G\M\T', $time);
    $if_none_match = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
    $if_modified_since = (string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '');
    $not_modified = $if_none_match && hash_equals($etag, $if_none_match);

    if (!$if_none_match && $if_modified_since) {
        $modified_since = strtotime($if_modified_since);
        $not_modified = false !== $modified_since && $modified_since >= $time;
    }

    header('Content-Type: '.$mime_type);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=86400');
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
    header("Last-Modified: {$last_modified}");
    header("ETag: {$etag}");

    if ($not_modified) {
        status_header(304);
        exit;
    }

    header('Content-Length: '.filesize($real_file));
    readfile($real_file);
    exit;
}

function podlove_image_cache_fail($status, $retry_after = null)
{
    status_header((int) $status);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');

    if ($retry_after) {
        header('Retry-After: '.(int) $retry_after);
    }

    exit;
}
