<?php 
use Podlove\Model\Image;
use Symfony\Component\Yaml\Yaml;
use Podlove\Cache\HttpHeaderValidator;

/**
 * WP Cron: Image cache validation
 */
add_action('wp', function() {
	if (!wp_next_scheduled('podlove_validate_image_cache'))
		wp_schedule_event(time(), 'daily', 'podlove_validate_image_cache');
});

add_action('podlove_validate_image_cache', 'podlove_validate_image_cache');
add_action('podlove_refetch_cached_image', 'podlove_refetch_cached_image', 10, 2);

function podlove_validate_image_cache() {
	set_time_limit(5 * MINUTE_IN_SECONDS);

	PHP_Timer::start();
	$cache_files = glob(trailingslashit(Image::cache_dir()) . "*" . DIRECTORY_SEPARATOR . "*" . DIRECTORY_SEPARATOR . "cache.yml");
	foreach ($cache_files as $cache_file) {
		$cache = Yaml::parse(file_get_contents($cache_file));

		$validator = new HttpHeaderValidator($cache['source'], $cache['etag'], $cache['last-modified']);
		$validator->validate();
		if ($validator->hasChanged()) {
			wp_schedule_single_event(time(), 'podlove_refetch_cached_image', [$cache['source'], $cache['filename']]);
		}
	}
	
	$time = PHP_Timer::stop();
	\Podlove\Log::get()->addInfo(sprintf('Finished validating %d images in %s', count($cache_files), PHP_Timer::secondsToTimeString($time)));
}

function podlove_refetch_cached_image($url, $filename) {
	(new Image($url, $filename))->redownload_source();
}

// add routes
add_action( 'init', function () {
    add_rewrite_rule(
        '^podlove/image/([^/]+)/([0-9]+)/([0-9]+)/([0-9])/([^/]+)/?$',
        'index.php?image_cache_url=$matches[1]&width=$matches[2]&height=$matches[3]&crop=$matches[4]&file_name=$matches[5]',
        'top'
    );
}, 10 );

add_filter( 'query_vars', function ( $query_vars ){
    $query_vars[] = 'image_cache_url';
    $query_vars[] = 'width';
    $query_vars[] = 'height';
    $query_vars[] = 'crop';
    $query_vars[] = 'file_name';
    return $query_vars;
}, 10, 1 );

add_action('wp', 'podlove_handle_cache_files');

function podlove_handle_cache_files() {

	$source_url = urldecode(podlove_get_query_var('image_cache_url'));
	$file_name  = urldecode(podlove_get_query_var('file_name'));
	$width  = (int) podlove_get_query_var('width');
	$height = (int) podlove_get_query_var('height');
	$crop   = (bool) podlove_get_query_var('crop');

	if (!$source_url)
		return;

	// tell WP Super Cache to not cache download links
	if ( ! defined( 'DONOTCACHEPAGE' ) )
		define( 'DONOTCACHEPAGE', true );

	$image = (new Image($source_url, $file_name));

	if (!$image->source_exists()) {
		$image->download_source();
	}

	// bail if download fails
	if (!$image->source_exists()) {
		status_header(404);
		exit;
	}

	$image
		->setWidth($width)
		->setHeight($height)
		->setCrop($crop);

	if (!file_exists($image->resized_file())) {
		$image->generate_resized_copy();
	}

	$file = $image->resized_file();

	$imageInfo = getimagesize($file);
	switch ($imageInfo[2]) {
		case IMAGETYPE_JPEG:
			header("Content-Type: image/jpeg");
		break;
		case IMAGETYPE_GIF:
			header("Content-Type: image/gif");
		break;
		case IMAGETYPE_PNG:
			header("Content-Type: image/png");
		break;
	}

	header('Content-Length: ' . filesize($file));
	header('Cache-Control: public, max-age=86400');
	header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

	$time = filemtime($file);
	$etag = md5($time . $source_url);
	$last_modified = gmdate("D, d M Y H:i:s \G\M\T", $time);

	$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
	$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : false;

	if ((($if_none_match && $if_none_match == $etag) || (!$if_none_match)) &&
	    ($if_modified_since && $if_modified_since == $last_modified))
	{
	    header('HTTP/1.1 304 Not Modified');
	} else {
		header("Last-Modified: $last_modified");
		header("ETag: $etag");

		readfile($file);
	}
	exit;
}
