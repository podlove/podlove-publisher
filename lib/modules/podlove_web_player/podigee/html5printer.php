<?php 
namespace Podlove\Modules\PodloveWebPlayer\Podigee;

use Podlove\Model\Episode;
use Podlove\Model\Feed;

class Html5Printer implements \Podlove\Modules\PodloveWebPlayer\PlayerPrinterInterface {

	// Model\Episode
	private $episode;

	private $config_var_name = null;

	public function __construct(Episode $episode) {
		$this->episode = $episode;
	}

	public function render($context = NULL, $style = 'configfile') {

		$src = 'http://cdn.podigee.com/podcast-player/javascripts/podigee-podcast-player.js';

		if ($style == 'inline') { // inline players are not embeddable
			return '
			<script>window.' . $this->config_var_name() . ' = ' . json_encode(self::config($this->episode, $context)) . '</script>
			<script class="podigee-podcast-player" src="' . $src . '" data-configuration="' . $this->config_var_name() . '"></script>';
		} else {
			return '<script class="podigee-podcast-player" src="' . $src . '" data-configuration="' . $this->config_url() . '"></script>';
		}

	}

	public function config_url() {
		return esc_url( add_query_arg('podigee_player', $this->episode->id, get_option('siteurl')) );
	}

	public static function config($episode, $context) {
		$post               = get_post($episode->post_id);
		$player_media_files = new \Podlove\Modules\PodloveWebPlayer\PlayerV3\PlayerMediaFiles($episode);
		$media_files        = $player_media_files->get($context);
		$media_files_conf   = array_reduce($media_files, function($agg, $item) {

			$extension = $item['extension'];

			if ($extension == 'oga') {
				$extension = 'ogg';
			}

			$agg[$extension] = $item['url'];

			return $agg;
		}, []);

		$config = [
			'options' => [
				'theme' => \Podlove\get_webplayer_setting('podigeetheme'),
				'startPanel' => in_array(\Podlove\get_webplayer_setting("chaptersVisible"), [true, 'true', 'on', 1, "1"], true) ? "ChapterMarks" : 'false'
			],
			'extensions' => [
				'EpisodeInfo' => [
					'showOnStart' => false
				],
				'ChapterMarks' => [
					'showOnStart' => false
				],
				'Share' => []
			],
			'podcast' => [
				// don't provide the feed unless we have a CORS solution
				// 'feed' => Feed::first()->get_subscribe_url()
			],
			'episode' => [
				'media' => $media_files_conf,
				'title' => get_the_title($post->ID),
				'subtitle' => wptexturize(convert_chars(trim($episode->subtitle))),
				'description' => nl2br(wptexturize(convert_chars(trim($episode->summary)))),
				'coverUrl' => $episode->cover_art_with_fallback()->setWidth(500)->url(),
				'chaptermarks' => json_decode($episode->get_chapters('json')),
				'url' => get_permalink($post->ID)
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
