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
	 * @deprecated since 2.2.0, use `image` instead
	 * @accessor
	 */
	public function imageUrl() {
		return new Image($this->podcast->cover_art());
	}

	/**
	 * Image
	 *
	 * @see  image
	 * @accessor
	 */
	public function image() {
		return new Image($this->podcast->cover_art());
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
	 * Podcast Home URL
	 * 
	 * @accessor
	 */
	public function landingPageUrl() {
		return $this->podcast->landing_page_url();
	}

	/**
	 * Episodes
	 *
	 * Filter and order episodes with parameters:
	 * 
	 * - post_id: one episode matching the given post id
	 * - post_ids: list of episodes matching the given list of post ids
	 * - category: list of episodes matching the category slug
	 * - slug: one episode matching the given slug
	 * - slugs: list of episodes matching the given list of slugs
	 * - post_status: Publication status of the post. Defaults to 'publish'
	 * - order: Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'DESC'.
	 *   - 'ASC' - ascending order from lowest to highest values (1, 2, 3; a, b, c).
	 *   - 'DESC' - descending order from highest to lowest values (3, 2, 1; c, b, a).
	 * - orderby: Sort retrieved episodes by parameter. Defaults to 'publicationDate'.
	 *   - 'publicationDate' - Order by publication date.
	 *   - 'recordingDate' - Order by recording date.
	 *   - 'title' - Order by title.
	 *   - 'slug' - Order by episode slug.
	 *	 - 'limit' - Limit the number of returned episodes.
	 *
	 * **Examples**
	 *
	 * Iterate over all published episodes, ordered by publication date.
	 *
	 * ```
	 * {% for e in podcast.episodes %}
	 *   {{ e.title }}
	 * {% endfor %}
	 * ```
	 *
	 * Order by title in ascending order.
	 * 
	 * ```
	 * {% for e in podcast.episodes({orderby: 'title', 'order': 'ASC'}) %}
	 *   {{ e.title }}
	 * {% endfor %}
	 * ```
	 *
	 * Fetch one episode by slug.
	 * 
	 * ```
	 * {{ podcast.episodes({slug: 'pod001'}).title }}
	 * ```
	 * 
	 * @see episode
	 * @accessor
	 */
	public function episodes($args = []) {
		$episodes = $this->podcast->episodes($args);

		if (is_array($episodes)) {
			return array_map(function ($episode) {
				return new Episode($episode);
			}, $episodes);
		} else {
			return new Episode($episodes);
		}
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
		}, $this->podcast->feeds());
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

	/**
	 * Get a podcast setting.
	 *
	 * Valid namespaces / names:
	 *
	 *  ```
	 *  website
	 *  	merge_episodes
	 *  	hide_wp_feed_discovery
	 *  	use_post_permastruct
	 *  	custom_episode_slug
	 *  	episode_archive
	 *  	episode_archive_slug
	 *  	url_template
	 *  	ssl_verify_peer
	 *  metadata
	 *  	enable_episode_recording_date
	 *  	enable_episode_explicit
	 *  	enable_episode_license
	 *  redirects
	 *  	podlove_setting_redirect
	 *  tracking
	 *  	mode
	 *  ```
	 *
	 * @accessor
	 */
	public function setting($namespace, $name) {
		return \Podlove\get_setting($namespace, $name);
	}

}