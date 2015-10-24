<?php
namespace Podlove;

use Podlove\Model;
use Podlove\Chapters\Printer;
use Podlove\Chapters\Parser;
use Podlove\Chapters\Chapters;

/**
 * Convenience wrapper for episode chapters.
 *
 * Handles caching of chapters.
 */
class ChaptersManager {
	public $episode;

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
		$chapters_object = $this->get_chapters_object();
		$chapters = '';

		switch ( $format ) {
			case 'psc':
				$chapters_object->setPrinter( new Printer\PSC() );
				$chapters = (string) $chapters_object;
				break;

			case 'mp4chaps':
				$chapters_object->setPrinter( new Printer\Mp4chaps() );
				$chapters = (string) $chapters_object;
				break;

			case 'json':
				$chapters_object->setPrinter( new Printer\JSON() );
				$chapters = (string) $chapters_object;
				break;

			case 'object':
				$chapters = $chapters_object;
				break;
		}

		return apply_filters( 'podlove_get_chapters', $chapters, $format, $chapters_object );
	}

	protected function get_raw_chapters_string($chapters_file) {
		$cache_key = 'podlove_chapters_string_' . $this->episode->id;

		if ( false === ( $chapters_string = get_transient( $cache_key ) ) ) {
			$request = wp_remote_get( $chapters_file->get_file_url() );

			if ( is_wp_error( $request ) )
				return '';

			// 1 year, we devalidate manually
			$chapters_string = $request['body'];
			set_transient( $cache_key, $chapters_string, 60*60*24*365 );
		}

		return $chapters_string;
	}

	private function get_chapters_object() {
		global $wpdb;

		// Get all posible assets with a chapter file format
		$chapter_assets = Model\EpisodeAsset::find_all_by_sql(
			"SELECT ea.*, ft.mime_type
			 FROM {$wpdb->prefix}podlove_episodeasset AS ea
			 INNER JOIN {$wpdb->prefix}podlove_filetype AS ft ON ft.id = ea.file_type_id
			 WHERE ft.type = 'chapters'
			 ORDER BY position ASC"
		);

		// Get IDs
		$chapter_assets_id_to_mimetype = array();
		foreach ( $chapter_assets as $chapter_asset ) {
			$chapter_assets_id_to_mimetype[$chapter_asset->id] = $chapter_asset->mime_type;
		}

		// Try to find attached chapter file to this episode
		$chapter_assets_ids = implode( ',', array_keys( $chapter_assets_id_to_mimetype ) );
		$chapters_file = Model\MediaFile::find_one_by_where( sprintf(
			"episode_id = %d
			 AND episode_asset_id IN (%s)
			 ORDER BY FIELD(episode_asset_id, %s)",
			$this->episode->id, $chapter_assets_ids, $chapter_assets_ids
		) );

		// Fallback to manual entry if no file was attached
		if ( null === $chapters_file )
			return Parser\Mp4chaps::parse( $this->episode->chapters );

		// Get mimetype for attached chapter file
		$mime_type = $chapter_assets_id_to_mimetype[$chapters_file->episode_asset_id];

		// Get chapters object for mine type
		switch ( $mime_type ) {
			case 'application/xml':
				$contents = $this->get_raw_chapters_string( $chapters_file );
				$chapters = Parser\PSC::parse( $contents );

				break;

			case 'application/json':
				$contents = $this->get_raw_chapters_string( $chapters_file );
				$chapters = Parser\JSON::parse( $contents );

				break;

			case 'text/plain':
				$contents = $this->get_raw_chapters_string( $chapters_file );

				switch ( $contents[0] ) {
					case '[':
					case '{':
						$chapters = Parser\JSON::parse( $contents );
						break;
					case '<':
						$chapters = Parser\PSC::parse( $contents );
						break;
					default:
						$chapters = Parser\Mp4chaps::parse( $contents );
						break;
				}

				break;

			default:
				$chapters = '';
		}

		// Apply filter
		return apply_filters(
			'podlove_get_chapters_object',
			( '' === $chapters || null === $chapters ) ? new Chapters : $chapters,
			$mime_type,
			$chapters_file,
			$this
		);
	}
}
