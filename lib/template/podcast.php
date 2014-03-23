<?php
namespace Podlove\Template;

/**
 * Podcast Template Wrapper
 *
 * @templatetag podcast
 */
class Podcast extends Wrapper {

	/**
	 * @var Podlove\Model\Podcast
	 */
	private $podcast;

	public function __construct(\Podlove\Model\Podcast $podcast) {
		$this->podcast = $podcast;
	}

	protected function getExtraFilterArgs() {
		return array($this->podcast);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * Title
	 *
	 * @accessor
	 */
	public function title() {
		return $this->podcast->title;
	}

	/**
	 * Subtitle
	 *
	 * @accessor
	 */
	public function subtitle() {
		return $this->podcast->subtitle;
	}

	/**
	 * Summary
	 *
	 * @accessor
	 */
	public function summary() {
		return $this->podcast->summary;
	}

	/**
	 * Image URL
	 *
	 * @accessor
	 */
	public function imageUrl() {
		return $this->podcast->cover_image;
	}

	/**
	 * Author name
	 *
	 * @accessor
	 */
	public function authorName() {
		return $this->podcast->author_name;
	}

	/**
	 * Owner name
	 *
	 * @accessor
	 */
	public function ownerName() {
		return $this->podcast->owner_name;
	}

	/**
	 * Owner email
	 *
	 * @accessor
	 */
	public function ownerEmail() {
		return $this->podcast->owner_email;
	}

	/**
	 * Publisher name
	 *
	 * @accessor
	 */
	public function publisherName() {
		return $this->podcast->publisher_name;
	}

	/**
	 * Publisher URL
	 *
	 * @accessor
	 */
	public function publisherUrl() {
		return $this->podcast->publisher_url;
	}

	/**
	 * Episodes
	 * 
	 * @see episode
	 * @accessor
	 */
	public function episodes() {
		$episodes = array();

		foreach (\Podlove\Model\Episode::allByTime() as $episode) {
			if ($episode->is_valid() && get_post($episode->post_id)->post_status == 'publish')
				$episodes[] = new Episode($episode);
		}

		return $episodes;
	}

	/**
	 * Feeds
	 *
	 * @see  feed
	 * @accessor
	 */
	public function feeds() {
		return array_map(function ($feed) {
			return new Feed($feed);
		}, \Podlove\Model\Feed::all('ORDER BY position ASC'));
	}

	/**
	 * License
	 *
	 * To render an HTML license, use 
	 * `{% include '@core/license.twig' with {'license': podcast.license} %}`
	 *
	 * @see  license
	 * @accessor
	 */
	public function license() {
		return new License(
			new \Podlove\Model\License(
				"podcast",
				array(
					'license_name'         => $this->podcast->license_name,
					'license_url'          => $this->podcast->license_url
				)
			)
		);
	}

}