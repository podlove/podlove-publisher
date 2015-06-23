<?php 
namespace Podlove\Modules\PodloveWebPlayer\Playerv3;

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Model\EpisodeAsset;
use Podlove\Model\MediaFile;

class PlayerMediaFiles {

	private $episode;

	private $audio_formats = ['mp3', 'mp4', 'ogg', 'opus'];
	private $video_formats = ['mp4', 'ogg', 'webm'];

	// List of Model\MediaFile
	private $files = [];

	public $media_xml_tag = '';

	public function __construct(Episode $episode) {
		$this->episode = $episode;
	}

	public function get($context = null) {
		$this->files = $this->get_files();
		$media_files = $this->media_files($context);

		if (empty($media_files))
			return '';

		return $this->sort_files($media_files);
	}

	private function media_files($context) {

		$context = is_null($context) ? $this->get_tracking_context() : $context;

		$media_files = [];
		foreach ($this->files as $file) {
			$asset = $file->episode_asset();
			$mime  = $asset->file_type()->mime_type;
			$media_files[$mime] = [
				'file'       => $file,
				'mime_type'  => $mime,
				'url'        => $file->get_file_url(),
				'publicUrl'  => $file->get_public_file_url("webplayer", $context),
				'assetTitle' => $asset->title(),
				'size' => $file->size
			];
		}

		return $media_files;
	}

	/**
	 * Sort files bases on mime type so preferred get output first.
	 */
	private function sort_files($media_files) {
		
		$sorted_files = [];
		$preferred_order = ['audio/mp4', 'audio/aac', 'audio/opus', 'audio/ogg', 'audio/vorbis'];
		
		foreach ($preferred_order as $order_key) {
			if (isset($media_files[$order_key]) && $media_files[$order_key]) {
				$sorted_files[] = $media_files[$order_key];
				unset($media_files[$order_key]);
			}
		}
		
		foreach ($media_files as $file) {
			$sorted_files[] = $file;
		}

		return $sorted_files;
	}

	private function get_files() {

		$files = $this->get_playable_video_files();
		$this->media_xml_tag = 'video';

		if ( count( $files ) == 0 ) {
			$files = $this->get_playable_audio_files();
			$this->media_xml_tag = 'audio';
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

		$playable_files = [];
		$player_format_assignments = get_option('podlove_webplayer_formats');

		if (empty($player_format_assignments)) {
			error_log(print_r("Podlove Web Player: No assets are assigned.", true));
			return [];
		}

		foreach ( $formats as $format ) {

			if ( ! isset( $player_format_assignments[ $media_type ][ $format ] ) )
				continue;

			$episode_asset = EpisodeAsset::find_by_id( $player_format_assignments[ $media_type ][ $format ] );
			if ( ! $episode_asset )
				continue;

			$media_file = MediaFile::find_by_episode_id_and_episode_asset_id( $this->episode->id, $episode_asset->id );
			if ( $media_file && $media_file->is_valid() )
				$playable_files[] = $media_file;
		}

		return $playable_files;
	}

	private function get_tracking_context() {
		if (is_home())
			return "home";

		if (is_single())
			return "episode";

		return "website";
	}

}