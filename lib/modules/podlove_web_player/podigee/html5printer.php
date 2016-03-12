<?php 
namespace Podlove\Modules\PodloveWebPlayer\Podigee;

use Podlove\Model\Episode;
use Podlove\Model\Feed;

class Html5Printer implements \Podlove\Modules\PodloveWebPlayer\PlayerPrinterInterface {

	// Model\Episode
	private $episode;
	private $post;

	private $config_var_name = null;

	public function __construct(Episode $episode) {
		$this->episode = $episode;
		$this->post    = get_post($episode->post_id);
	}

	public function render($context = NULL) {

		$src = 'http://cdn.podigee.com/podcast-player/javascripts/podigee-podcast-player.js';

		return '
		<script>window.' . $this->config_var_name() . ' = ' . json_encode($this->config($context)) . '</script>
		<script class="podigee-podcast-player" src="' . $src . '" data-configuration="' . $this->config_var_name() . '"></script>';
	}

	public function config($context) {
		$player_media_files = new \Podlove\Modules\PodloveWebPlayer\PlayerV3\PlayerMediaFiles($this->episode);
		$media_files = $player_media_files->get($context);

		$config = [
			'options' => [
				'theme' => 'default'
			],
			'extensions' => [
				'EpisodeInfo' => [
					'showOnStart' => false
				],
				'ChapterMarks' => [
					'showOnStart' => false
				]
			],
			'podcast' => [
				'feed' => Feed::first()->get_subscribe_url()
			],
			'episode' => [
				'media' => [],
				'title' => get_the_title($this->post->ID),
				'subtitle' => wptexturize(convert_chars(trim($this->episode->subtitle))),
				'description' => nl2br(wptexturize(convert_chars(trim($this->episode->summary)))),
				'coverUrl' => $this->episode->cover_art_with_fallback()->setWidth(500)->url(),
				'chaptermarks' => json_decode($this->episode->get_chapters('json'))
			]
		];

		foreach ($media_files as $file) {

			switch ($file['mime_type']) {
				case 'audio/mp4':  $ext = 'm4a'; break;
				case 'audio/opus': $ext = 'opus'; break;
				case 'audio/ogg':  $ext = 'ogg'; break;
				case 'audio/mpeg': $ext = 'mp3'; break;
				
				default: $ext = false; break;
			}

			if ($ext) {
				$config['episode']['media'][$ext] = $file['publicUrl'];
			}
		}

		return $config;
	}

	private function config_var_name() {

		if (!$this->config_var_name) {
			$uuid = str_replace(".", "", uniqid('', true));
			$this->config_var_name = 'player_' . $uuid;
		}

		return $this->config_var_name;
	}
}
