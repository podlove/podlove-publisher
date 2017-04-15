<?php 
namespace Podlove\Modules\PodloveWebPlayer\PlayerV4;

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Modules\PodloveWebPlayer\PlayerV3\PlayerMediaFiles;

class Html5Printer implements \Podlove\Modules\PodloveWebPlayer\PlayerPrinterInterface {

	// Model\Episode
	private $episode;

	private $attributes = [];

	public function __construct(Episode $episode) {
		$this->episode = $episode;
	}

	public function render($context = NULL) {

		$html = '<div id="player"></div>';
		$html.= '<script>';
		$html.= 'podlovePlayer("#player", ' . json_encode(self::config($this->episode, $context)) . ')';
		$html.= '</script>';

		return $html;
	}

	public static function config($episode, $context) {

		$post = get_post($episode->post_id);
		$podcast = Podcast::get();

		$player_media_files = new PlayerMediaFiles($episode);
		$media_files        = $player_media_files->get($context);
		$media_file_urls = array_map(function($file) {
			return $file['publicUrl'];
		}, $media_files);

		$config = [
			'show' => [
				'title'    => $podcast->title,
				'subtitle' => $podcast->subtitle,
				'summary'  => $podcast->summary,
				'poster'   => $podcast->cover_art()->setWidth(500)->url(),
			],
			'title'           => $post->post_title,
			'subtitle'        => wptexturize(convert_chars(trim($episode->subtitle))),
			'summary'         => nl2br(wptexturize(convert_chars(trim($episode->summary)))),
			'publicationDate' => mysql2date("c", $post->post_date),
			'poster'          => $episode->cover_art_with_fallback()->setWidth(500)->url(),
			'duration'        => $episode->get_duration('full'),
			'audio' => $media_file_urls,
			'reference' => [
				'base'   => plugins_url('dist', __FILE__),
				'share'  => trailingslashit(plugins_url('dist', __FILE__)) . 'share.html',
				'config' => self::config_url($episode)
			],
			'chapters' => array_map(function($c) {
				$c->title = html_entity_decode(wptexturize(convert_chars(trim($c->title))));
				return $c;
			}, json_decode($episode->get_chapters('json')))
		];

		return $config;
	}

	public static function config_url($episode) {
		return esc_url( add_query_arg('podlove_player4', $episode->id, trailingslashit(get_option('siteurl'))) );
	}
}
