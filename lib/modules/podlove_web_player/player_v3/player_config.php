<?php 
namespace Podlove\Modules\PodloveWebPlayer\PlayerV3;

use Podlove\Model;
use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Model\EpisodeAsset;
use Podlove\Model\MediaFile;

class PlayerConfig {

	private $episode;
	private $post;
	private $podcast;

	public function __construct(Episode $episode) {
		$this->episode = $episode;
		$this->post    = get_post($episode->post_id);
		$this->podcast = Podcast::get();
	}

	private function get_license() {
		if ($this->episode->license_name && $this->episode->license_url) {
			return [
				'name' => $this->episode->license_name,
				'url'  => $this->episode->license_url
			];
		} else {
			return [
				'name' => $this->podcast->license_name,
				'url'  => $this->podcast->license_url
			];
		}
	}

	private function get_downloads() {
		$player_media_files = new PlayerMediaFiles($this->episode);
		$media_files = $player_media_files->get();

		$downloads = [];
		foreach ($media_files as $file) {
			$downloads[] = [
				'assetTitle'   => $file['assetTitle'],
				'size'         => $file['size'],
				'downloadUrl'  => $file['publicUrl'],
				'directAccess' => $file['url'],
				'url'          => $file['url']
			];
		}

		return $downloads;
	}

	public function get() {

		$license = $this->get_license();
		$downloads = $this->get_downloads();

		$config = [
            "alwaysShowHours" => true,
            "alwaysShowControls" => true,
            "chaptersVisible" => true,
            "permalink" => get_permalink($this->post->ID),
            "publicationDate" => mysql2date("c", $this->post->post_date),
            "title" => get_the_title($this->post->ID),
            "subtitle" => wptexturize(convert_chars(trim($this->episode->subtitle))),
            "summary" => nl2br(wptexturize(convert_chars(trim($this->episode->summary)))), 
            "poster" => $this->episode->cover_art_with_fallback()->setWidth(500)->url(),
            "show" => [
				'title'    => $this->podcast->title,
				'subtitle' => $this->podcast->subtitle,
				'summary'  => $this->podcast->summary,
				'poster'   => $this->podcast->cover_art()->setWidth(500)->url(),
				'url'      => \Podlove\get_landing_page_url()
			],
            "license" => [
                "name" => $license['name'],
                "url"  => $license['url']
            ],
            "downloads" => $downloads,
            "duration" => $this->episode->get_duration(),
            "features" => ["current", "progress", "duration", "tracks", "fullscreen", "volume"],
            "chapters" => json_decode($this->episode->get_chapters('json')),
            "languageCode" => $this->podcast->language
         ];

         return $config;
	}

}