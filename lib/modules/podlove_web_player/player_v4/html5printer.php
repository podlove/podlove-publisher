<?php 
namespace Podlove\Modules\PodloveWebPlayer\PlayerV4;

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Modules\PodloveWebPlayer\PlayerV3\PlayerMediaFiles;
use Podlove\Modules\Social\Model\ContributorService;

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
			return $file['publicUrl'];
		}, $media_files);

		$player_settings = \Podlove\get_webplayer_settings();

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
			}, (array) json_decode($episode->get_chapters('json'))),
			'theme' => [
				'primary'   => $player_settings['playerv4_color_primary'],
				'secondary' => $player_settings['playerv4_color_secondary']
			]
		];

		$contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($episode->id);
		$contributors = array_map(function ($c)	{
			$role = $c->getRole();
			$group = $c->getGroup();
			$contributor = $c->getContributor();

			$services = ContributorService::find_by_contributor_id_and_category($contributor->id, 'social');

			return [
				'name'  => $contributor->getName(),
				'image' => $contributor->avatar()->url(),
				'role'  => $role  ? $role->title  : '',
				'group' => $group ? $group->title : '',
				'social' => array_map(function ($s) {
					$service = $s->get_service();
					return [
						'service' => $service->title,
						'type'    => $service->type,
						'url'     => $s->get_service_url(),
						'image'   => $service->image()->url()
					];
				}, $services)
			];
		}, $contributions);

		$config['contributors'] = $contributors;

		return $config;
	}

	public static function config_url($episode) {
		return esc_url( add_query_arg('podlove_player4', $episode->id, trailingslashit(get_option('siteurl'))) );
	}
}
