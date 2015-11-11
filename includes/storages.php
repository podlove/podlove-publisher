<?php
use \Podlove\Storage;

add_action('init', 'podlove_init_storages');

add_filter('podlove_storage_classes', function ($classes) {
	$classes[] = '\Podlove\Storage\WordpressStorage';
	$classes[] = '\Podlove\Storage\ExternalStorage';
	return $classes;
});

function podlove_init_storages() {
	$controller = new Storage\StorageController;
	$controller->register();

	 // ensure storages are initialized
	\Podlove\Model\Podcast::get();
}
