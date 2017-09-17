<?php 
namespace Podlove\Modules\PodloveWebPlayer\PlayerV4;

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Modules\PodloveWebPlayer\PlayerV3\PlayerMediaFiles;
use Podlove\Modules\Contributors\Model\EpisodeContribution;

class Html5Printer implements \Podlove\Modules\PodloveWebPlayer\PlayerPrinterInterface {

	// Model\Episode
	private $episode;

	private $player_id;

	private $attributes = [];

	public function __construct(Episode $episode) {
		$this->episode = $episode;
	}

	private function get_player_id() {

		if ( ! $this->player_id ) {
			$this->player_id = 'podlovewebplayer_' . sha1(microtime() . rand());
		}

		return $this->player_id;
	}

	public function render($context = NULL) {

		$id = $this->get_player_id();

		$html = '<div id="' . $id . '"></div>';
		$html.= '<script>';
		$html.= 'podlovePlayer("#' . $id . '", ' . json_encode(self::config($this->episode, $context)) . ')';
		$html.= '</script>';

		return $html;
	}

	public static function config($episode, $context) {

		$post = get_post($episode->post_id);
		$podcast = Podcast::get();

		$player_media_files = new PlayerMediaFiles($episode);
		$media_files        = $player_media_files->get($context);
		$media_file_urls = array_map(function($file) {
			return [
				'url'      => $file['publicUrl'],
				'size'     => $file['size'],
				'title'    => $file['assetTitle'],
				'mimeType' => $file['mime_type']
			];
		}, $media_files);

		$player_settings = \Podlove\get_webplayer_settings();

		$config = [
			'show' => [
				'title'    => $podcast->title,
				'subtitle' => $podcast->subtitle,
				'summary'  => $podcast->summary,
				'poster'   => $podcast->cover_art()->setWidth(500)->url(),
				'link'     => \Podlove\get_landing_page_url()
			],
			'title'           => $post->post_title,
			'subtitle'        => wptexturize(convert_chars(trim($episode->subtitle))),
			'summary'         => nl2br(wptexturize(convert_chars(trim($episode->summary)))),
			'publicationDate' => mysql2date("c", $post->post_date),
			'poster'          => $episode->cover_art_with_fallback()->setWidth(500)->url(),
			'duration'        => $episode->get_duration('full'),
			'link'            => get_permalink($post->id),
			'audio' => $media_file_urls,
			'reference' => [
				'base'   => plugins_url('dist', __FILE__),
				'share'  => trailingslashit(plugins_url('dist', __FILE__)) . 'share.html',
				'config' => self::config_url($episode)
			],
			'chapters' => array_map(function($c) {
				$c->title = html_entity_decode(wptexturize(convert_chars(trim($c->title))));
				return $c;
			}, (array) json_decode($episode->get_chapters('json'))),
			'theme' => [
				'main'      => $player_settings['playerv4_color_primary'],
				'highlight' => $player_settings['playerv4_color_secondary']
			]
		];

		if (\Podlove\Modules\Base::is_active('contributors')) {
			$config['contributors'] = array_map(function ($c) {
				$contributor = $c->getContributor();
				return [
					'name'   => $contributor->getName(),
					'avatar' => $contributor->avatar()->setWidth(150)->setHeight(150)->url(),
					'role' => $c->hasRole() ? $c->getRole()->to_array() : null,
					'group' => $c->hasGroup() ? $c->getGroup()->to_array() : null,
					'comment' => $c->comment
				];
			}, EpisodeContribution::find_all_by_episode_id($episode->id));
		}

		return $config;
	}

	public static function config_url($episode) {
		return esc_url( add_query_arg('podlove_player4', $episode->id, trailingslashit(get_option('siteurl'))) );
	}
}
