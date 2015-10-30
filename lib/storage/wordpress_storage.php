<?php
namespace Podlove\Storage;

class WordpressStorage implements StorageInterface {

	public static function key() {
		return 'wordpress';
	}

	public static function description() {
		return __('WordPress Upload', 'podlove');
	}

	function __construct() {
		add_filter('podlove_media_storage_options', [$this, 'add_storage_option']);
	}

	public function add_storage_option($options) {
		$options[self::key()] = self::description();
		return $options;
	}
}
