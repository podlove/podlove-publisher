<?php
namespace Podlove\Modules\Bitlove;
use \Podlove\Model;

class RssExtension {

	public static function init() {
		add_action( 'rss2_ns', array( __CLASS__, 'add_rss_namespace') );
		add_filter('podlove_feed_enclosure_attributes', array(__CLASS__, 'add_rss_enclosure_guid'), 10, 2);
	}

	public static function add_rss_namespace() {
		echo 'xmlns:bitlove="http://bitlove.org" ';
	}

	public static function add_rss_enclosure_guid($attributes, $media_file) {
		return array_merge($attributes, ['bitlove:guid' => self::get_enclosure_guid($media_file)]);
	}

	public static function get_enclosure_guid(\Podlove\Model\MediaFile $media_file) {
		return site_url("/podlove/file/" . $media_file->id);
	}

}