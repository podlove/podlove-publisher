<?php 
namespace Podlove\Modules\PodloveWebPlayer\Playerv3;

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Model\EpisodeAsset;
use Podlove\Model\MediaFile;

class HTML5Printer {

	// Model\Episode
	private $episode;

	private $audio_formats = ['mp3', 'mp4', 'ogg', 'opus'];
	private $video_formats = ['mp4', 'ogg', 'webm'];

	// determined player type, based on $files
	private $is_video = true;

	// List of Model\MediaFile
	private $files = [];

	public function __construct(Episode $episode) {
		$this->episode = $episode;
		$this->post    = get_post($episode->post_id);
		$this->player_format_assignments = get_option('podlove_webplayer_formats');
		$this->files = $this->get_files();
	}

	public function render($context = NULL) {

		if (empty($this->player_format_assignments)) {
			error_log(print_r("Podlove Web Player: No assets are assigned.", true));
			return '';
		}

		// build main audio/video tag
		$xml = new \SimpleXMLElement('<' . $this->get_media_tag() . '/>');
		$xml->addAttribute('controls', 'controls');
		$xml->addAttribute('preload', 'none');
		$xml->addAttribute('data-podlove-web-player-source', 'my-player.html');

		$media_files = $this->media_files($context);

		if (empty($media_files)) {
			error_log(print_r("Podlove Web Player: No media files.", true));
			return '';
		}

		$sorted_files = $this->sort_files($media_files);

		// add all sources
		$xml = $this->add_sources($xml, $sorted_files);

		// prettify and prepare to render
		$xml_string = $xml->asXML();
		// TODO: use DomDocumentFragment
		$xml_string = $this->format_xml($xml_string);
		$xml_string = $this->remove_xml_header($xml_string);

		return $xml_string;
	}

	public function add_sources($xml, $files) {

		$flash_fallback_func = function(&$xml) {};

		foreach ($files as $file) {
			$mime_type = $file['mime_type'];

			$source = $xml->addChild('source');
			$source->addAttribute('src', $file['publicUrl']);
			$source->addAttribute('type', $mime_type);

			if ($mime_type == 'audio/mpeg') {
				$flash_fallback_func = function(&$xml) use ($file) {
					$flash_fallback = $xml->addChild('object');
					$flash_fallback->addAttribute('type', 'application/x-shockwave-flash');
					$flash_fallback->addAttribute('data', plugins_url('bin/', __FILE__) . 'flashmediaelement.swf');

					$params = [
						[
							'name' => 'movie',
							'value' => plugins_url('player/podlove-web-player/static/', __FILE__) . 'flashmediaelement.swf'
						],
						[
							'name' => 'flashvars',
							'value' => 'controls=true&file=' . $file['url']
						]
					];

					foreach ($params as $param) {
						$p = $flash_fallback->addChild('param');
						$p->addAttribute('name', $param['name']);
						$p->addAttribute('value', $param['value']);
					}
					
				};
			}
		}
		// add flash fallback after all <source>s
		$flash_fallback_func($xml);

		return $xml;
	}

	private function format_xml( $xml ) {

		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML( $xml );

		return $dom->saveXML();
	}

	private function remove_xml_header( $xml ) {
		return trim( str_replace( '<?xml version="1.0"?>', '', $xml ) );
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
				'assetTitle' => $asset->title()
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

	private function get_media_tag() {
		return $this->is_video ? 'video' : 'audio';
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