<?php 
namespace Podlove\Feeds;
use Podlove\Model;

/**
 * Embed chapters into feed.
 */
class Chapters {

	private $episode;

	public function __construct( \Podlove\Model\Episode $episode ) {
		$this->episode = $episode;
	}

	/**
	 * Render chapters into feed.
	 * 
	 * @param  string $style 'inline' or 'link'. Default: link
	 */
	public function render( $style = 'link' ) {
		$asset_assignment = Model\AssetAssignment::get_instance();
		$chapters_string  = $this->get_raw_chapters_string();
		$chapters_asset   = Model\EpisodeAsset::find_one_by_id( $asset_assignment->chapters );
		$chapters = $this->get_chapters_object_from_string( $chapters_string, $chapters_asset );

		if ( $chapters )
			call_user_method_array( "render_$style", $this, array( $chapters ) );
	}

	public function render_inline( $chapters ) {
		$chapters->setPrinter( new \Podlove\Chapters\Printer\PSC() );
		echo $chapters;
	}

	public function render_link( $chapters ) {
		echo Model\Feed::get_link_tag(array(
			'prefix' => 'atom',
			'rel'    => 'http://podlove.org/simple-chapters',
			'type'   => '',
			'title'  => '',
			'href'   => get_permalink() . "?chapters_format=psc"
		));
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

	private function get_chapters_object_from_string( $chapters_string, $chapters_asset ) {

		$mime_type = $chapters_asset->file_type()->mime_type;
		$chapters  = false;

		switch ( $mime_type ) {
			case 'application/xml':
				$chapters = \Podlove\Chapters\Parser\PSC::parse( $chapters_string );
			break;
			case 'application/json':
				$chapters = \Podlove\Chapters\Parser\JSON::parse( $chapters_string );
				break;
			case 'text/plain':
				$chapters = \Podlove\Chapters\Parser\Mp4chaps::parse( $chapters_string );
				break;
		}

		return $chapters;
	}
}