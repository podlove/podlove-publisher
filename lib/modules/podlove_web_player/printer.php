<?php 
namespace Podlove\Modules\PodloveWebPlayer;

use Podlove\Model;
use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Model\EpisodeAsset;
use Podlove\Model\MediaFile;
use Podlove\Chapters\Parser;

/**
 * Print HTML & stuff required for Podlove Web Player
 */
class Printer {

	// Model\Episode
	private $episode;

	private $audio_formats = array( 'mp3', 'mp4', 'ogg', 'opus' );
	private $video_formats = array( 'mp4', 'ogg', 'webm' );

	// List of Model\MediaFile
	private $files = array();

	public function __construct( Episode $episode ) {
		$this->episode = $episode;
		$this->podcast = Podcast::get_instance();
		$this->post    = get_post($episode->post_id);
		$this->player_format_assignments = $this->get_player_format_assignments();
		$this->files = $this->get_files();
	}

	public function render() {
		$downloads = array();
		$sources = array();

		$chapters_raw = Parser\Mp4chaps::parse($this->episode->chapters);
		$chapters = "";
		if ($chapters_raw) {
			$chapters_raw->setPrinter( new \Podlove\Chapters\Printer\JSON() );
			$chapters = json_decode( (string) $chapters_raw );
		}

		foreach ($this->files as $file) {
			$downloads[] = array(
					'name' => $file['asset']->title,
					'size' => $file['file']->size,
					'url' => $file['file']->get_file_url(),
					'dlurl' => $file['file']->get_file_url()
				);
			$sources[] = array(
					'src' => $file['file']->get_file_url(),
					'type' => $file['type']->mime_type
				);
		}

		$player = new Player;
		// Configure Player
		$player->options = array(
				'sources' => $sources,
				'downloads' => $downloads,
				'chapters' => $chapters,

				'poster' => $this->episode->get_cover_art(),
				'title' => $this->post->post_title,
				'permalink' => get_permalink(),
				'subtitle' => $this->episode->subtitle,
				'publicationDate' => date( 'c', strtotime($this->episode->recording_date) ),
				'license' => array(
						'name' => ( empty($this->episode->license_name) ? $this->podcast->license_name : $this->episode->license_name ),
						'url' => ( empty($this->episode->license_url) ? $this->podcast->license_url : $this->episode->license_name )
					),
				'summary' => $this->episode->summary,
				'show' => array(
						'title' => $this->podcast->title,
						'subtitle' => $this->podcast->subtitle,
						'summary' => $this->podcast->summary,
						'poster' => $this->podcast->cover_image,
						'url' => get_home_url()
					),
				'duration' => $this->episode->duration,
				'alwaysShowHours' => true,
				'width' => 'auto',
				'summaryVisible' => false,
				'timecontrolsVisible' => false,
				'sharebuttonsVisible' => false,
				'chaptersVisible' => true
			);

		// And print it
		return $player->getPlayer( get_permalink() );
	}

	private function get_files() {

		$files = $this->get_playable_video_files();

		if ( count( $files ) == 0 ) {
			$files = $this->get_playable_audio_files();
			$this->is_video = false;
		}

		return $files;
	}

	private function get_playable_video_files() {
		return $this->get_playable_files( $this->video_formats, 'video' );
	}

	private function get_playable_audio_files() {
		return $this->get_playable_files( $this->audio_formats, 'audio' );
	}

	/**
	 * Get playable files for player, based on episode and player assignments.
	 * 
	 * @param  array  $formats      array of formats like mp3, mp3, ogg, opus, webm
	 * @param  string $media_type   audio or video
	 * @return array of \Podlove\Model\MediaFile
	 */
	private function get_playable_files( $formats, $media_type ) {

		$playable_files = array();

		foreach ( $formats as $format ) {

			if ( ! isset( $this->player_format_assignments[ $media_type ][ $format ] ) )
				continue;

			$episode_asset = EpisodeAsset::find_by_id( $this->player_format_assignments[ $media_type ][ $format ] );
			if ( ! $episode_asset )
				continue;

			$media_file = MediaFile::find_by_episode_id_and_episode_asset_id( $this->episode->id, $episode_asset->id );
			if ( $media_file && $media_file->is_valid() )
				$playable_files[] = array(
						'file' => $media_file,
						'asset' => $episode_asset,
						'type' => $episode_asset->file_type()
					);
		}

		return $playable_files;
	}

	private function get_player_format_assignments( $value='' ) {
		return get_option( 'podlove_webplayer_formats' );
	}
}