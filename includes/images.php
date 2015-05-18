<?php 
use Podlove\Model\Image;
use Symfony\Component\Yaml\Yaml;
use Podlove\Cache\HttpHeaderValidator;

/**
 * WP Cron: Download image url
 */
add_action('podlove_download_image_source', function($source_url, $file_name) {
	$image = new Image($source_url, $file_name);
	$image->download_source();
}, 10, 2);

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

	$cache_files = glob(trailingslashit(Image::cache_dir()) . "*" . DIRECTORY_SEPARATOR . "cache.yml");
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
