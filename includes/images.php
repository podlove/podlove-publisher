<?php 
use Podlove\Model\Image;

/**
 * WP Cron: Download image url
 */
add_action('podlove_download_image_source', function($source_url, $file_name) {
	$image = new Image($source_url, $file_name);
	$image->download_source();
}, 10, 2);
