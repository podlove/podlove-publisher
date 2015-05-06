<?php 
use Podlove\Model\Image;

/**
 * WP Cron: Download image url
 */
add_action('podlove_download_image_source', function($source_url) {
	$image = new Image($source_url);
	$image->download_source();
});
