<?php
namespace Podlove;

use Podlove\Model;
use Podlove\Chapters\Printer;

/**
 * Convenience wrapper for episode chapters.
 *
 * Handles caching of chapters.
 */
class ChaptersManager {

	private $episode;
	private $chapters_raw = '';
	private $chapters_object = NULL;

	public function __construct( Model\Episode $episode ) {
		$this->episode = $episode;
	}

	/**
	 * Get episode chapters.
	 * 
	 * @param  string $format object, psc, mp4chaps, json. Default: object
	 * @return mixed
	 */
	public function get( $format = 'object' ) {

		if ( ! $this->chapters_object )
			$this->chapters_object = $this->get_chapters_object();

		switch ( $format ) {
			case 'psc':
				$this->chapters_object->setPrinter( new Printer\PSC() );
				return (string) $this->chapters_object;
				break;
			case 'mp4chaps':
				$this->chapters_object->setPrinter( new Printer\Mp4chaps() );
				return (string) $this->chapters_object;
				break;
			case 'json':
				$this->chapters_object->setPrinter( new Printer\JSON() );
				return (string) $this->chapters_object;
				break;
		}

		return $this->chapters_object;
	}

	private function get_raw_chapters_string() {

		$asset_assignment = Model\AssetAssignment::get_instance();
		$cache_key = 'podlove_chapters_string_' . $this->episode->id;
		if ( ( $chapters_string = get_transient( $cache_key ) ) !== FALSE ) {
			return $chapters_string;
		} else {
			if ( $asset_assignment->chapters == 'manual' ) {
				return $this->episode->chapters;
			} else {
				if ( ! $chapters_asset = Model\EpisodeAsset::find_one_by_id( $asset_assignment->chapters ) )
					return '';

				if ( ! $chapters_file = Model\MediaFile::find_by_episode_id_and_episode_asset_id( $this->episode->id, $chapters_asset->id ) )
					return '';

				$chapters_string = wp_remote_get( $chapters_file->get_file_url() );

				if ( is_wp_error( $chapters_string ) )
					return '';

				set_transient( $cache_key, $chapters_string['body'], 60*60*24*365 ); // 1 year, we devalidate manually
				return $chapters_string['body'];
			}
		}	

	}

	private function get_chapters_object() {

		if ( ! $this->chapters_raw )
			$this->chapters_raw = $this->get_raw_chapters_string();

		$asset_assignment = Model\AssetAssignment::get_instance();
		$chapters_asset   = Model\EpisodeAsset::find_one_by_id( $asset_assignment->chapters );

		$mime_type = $chapters_asset->file_type()->mime_type;
		$chapters  = false;

		switch ( $mime_type ) {
			case 'application/xml':
				$chapters = \Podlove\Chapters\Parser\PSC::parse( $this->chapters_raw );
			break;
			case 'application/json':
				$chapters = \Podlove\Chapters\Parser\JSON::parse( $this->chapters_raw );
				break;
			case 'text/plain':
				$chapters = \Podlove\Chapters\Parser\Mp4chaps::parse( $this->chapters_raw );
				break;
		}

		return $chapters;
	}

}