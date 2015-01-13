<?php
namespace Podlove\Modules\Bitlove;
use \Podlove\Model;

class RssExtension {

	public static function init() {
		add_action( 'rss2_ns', array( __CLASS__, 'add_rss_namespace') );
		add_action( 'podlove_append_to_feed_entry', array( __CLASS__, 'add_rss_enclosure_guid'), 10, 4 );
	}

	public static function add_rss_namespace() {
		echo 'xmlns:bitlove="http://bitlove.org" ';
	}

	public static function add_rss_enclosure_guid($podcast, $episode, $feed, $format) {

		if (!$asset = $feed->episode_asset())
			return;

		if (!$media_file = Model\MediaFile::find_by_episode_id_and_episode_asset_id( $episode->id, $asset->id ))
			return;

		$guid = self::get_enclosure_guid($media_file);
		echo "<bitlove:enclosure-guid>$guid</bitlove:enclosure-guid>";
	}

	public static function get_enclosure_guid(\Podlove\Model\MediaFile $media_file) {
		return site_url("/podlove/file/" . $media_file->id);
	}

}