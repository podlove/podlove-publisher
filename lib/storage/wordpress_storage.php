<?php
namespace Podlove\Storage;

class WordpressStorage implements StorageInterface {

	public static function key() {
		return 'wordpress';
	}

	public static function description() {
		return __('WordPress Upload', 'podlove');
	}

	public function register() {
		add_filter('podlove_media_storage_options', [$this, 'add_storage_option']);
	}

	public function init() {
		new WordpressStorage\MediaMetaBox;
		add_filter('podlove_file_url', [$this, 'podlove_file_url'], 10, 4);
	}

	public function add_storage_option($options) {
		$options[self::key()] = self::description();
		return $options;
	}

	public function podlove_file_url($podcast, $episode, $episode_asset, $file_type) {

		$attachment_id = get_post_meta($episode->post_id, 'podlove_media_attachment_id', true);
		$url = wp_get_attachment_url($attachment_id);

		return $url;
	}
}
