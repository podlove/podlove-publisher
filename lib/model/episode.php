<?php
namespace Podlove\Model;

/**
 * We could use simple post_meta instead of a table here
 */
class Episode extends Base {

	/**
	 * Generate a human readable title.
	 * 
	 * Return name and, if available, the subtitle. Separated by a dash.
	 * 
	 * @return string
	 */
	public function full_title() {
		
		$post_id = $this->post_id;
		$post    = get_post( $post_id );
		$title   = $post->post_title;
		
		if ( $this->subtitle )
			$title = $title . ' - ' . $this->subtitle;
		
		return $title;
	}

	public function media_files() {
		return MediaFile::find_all_by_episode_id( $this->id );
	}

	public function find_or_create_by_post_id( $post_id ) {
		$episode = Episode::find_one_by_property( 'post_id', $post_id );

		if ( $episode )
			return $episode;

		$episode = new Episode();
		$episode->post_id = $post_id;
		$episode->save();

		return $episode;
	}

	public function enclosure_url( $episode_asset ) {
		$media_file = MediaFile::find_by_episode_id_and_episode_asset_id( $this->id, $episode_asset->id );
		return $media_file->get_file_url();
	}

	public function get_cover_art() {
		
		$podcast = Podcast::get_instance();

		if ( $podcast->supports_cover_art == 0 )
			return;

		if ( $podcast->supports_cover_art == 'manual' )
			return $this->cover_art;

		$cover_art_file_id = $podcast->supports_cover_art;
		if ( ! $asset = EpisodeAsset::find_one_by_id( $cover_art_file_id ) )
			return false;

		if ( ! $file = MediaFile::find_by_episode_id_and_episode_asset_id( $this->id, $asset->id ) )
			return false;

		return $file->get_file_url();
	}

	public function refetch_files() {
		foreach ( EpisodeAsset::all() as $asset ) {
			if ( $file = MediaFile::find_by_episode_id_and_episode_asset_id( $this->id, $asset->id ) ) {
				$file->determine_file_size();
				$file->save();
			}
		}
	}

	public function get_duration( $format = 'HH:MM:SS' ) {
		$duration = new \Podlove\Duration( $this->duration );
		return $duration->get( $format );
	}

}

Episode::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Episode::property( 'post_id', 'INT' );
Episode::property( 'show_id', 'INT' );
Episode::property( 'subtitle', 'VARCHAR(255)' );
Episode::property( 'summary', 'TEXT' );
Episode::property( 'active', 'INT' ); // publicized or not?
Episode::property( 'enable', 'INT' ); // listed in podcast directories or not?
Episode::property( 'slug', 'VARCHAR(255)' );
Episode::property( 'duration', 'VARCHAR(255)' );
Episode::property( 'cover_art', 'VARCHAR(255)' );
Episode::property( 'chapters', 'TEXT' );
// todo: add summary

// episode_number
// season_number
// episode_id nach template, virtual attribute