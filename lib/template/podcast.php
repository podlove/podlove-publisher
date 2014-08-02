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
	 * Filter and order episodes with parameters:
	 * 
	 * - post_id: one episode matching the given post id
	 * - post_ids: list of episodes matching the given list of post ids
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
	public function episodes($args = array()) {
		global $wpdb;

		if ( $blog_id = $this->podcast->blog_id )
			switch_to_blog( $blog_id );

		// fetch single episodes
		if (isset($args['post_id']))
			return new Episode(\Podlove\Model\Episode::find_one_by_post_id($args['post_id']));

		if (isset($args['slug']))
			return new Episode(\Podlove\Model\Episode::find_one_by_slug($args['slug']));

		// build conditions
		$where = "1 = 1";
		if (isset($args['post_ids'])) {
			$ids = array_filter( // remove "0"-IDs
				array_map( // convert elements to integers
					function($n) { return (int) trim($n); },
					$args['post_ids']
				)
			);

			if (count($ids))
				$where .= " AND p.ID IN (" . implode(",", $ids) . ")";
		}

		if (isset($args['slugs'])) {
			$slugs = array_filter( // remove empty slugs
				array_map( // trim
					function($n) { return "'" . trim($n) . "'"; },
					$args['slugs']
				)
			);

			if (count($slugs))
				$where .= " AND e.slug IN (" . implode(",", $slugs) . ")";
		}

		if (isset($args['post_status']) && in_array($args['post_status'], get_post_stati())) {
			$where .= " AND p.post_status = '" . $args['post_status'] . "'";
		} else {
			$where .= " AND p.post_status = 'publish'";
		}

		// order
		$order_map = array(
			'publicationDate' => 'p.post_date',
			'recordingDate'   => 'e.recordingDate',
			'slug'            => 'e.slug',
			'title'           => 'p.post_title'
		);

		if (isset($args['orderby']) && isset($order_map[$args['orderby']])) {
			$orderby = $order_map[$args['orderby']];
		} else {
			$orderby = $order_map['publicationDate'];
		}

		if (isset($args['order'])) {
			$args['order'] = strtoupper($args['order']);
			if (in_array($args['order'], array('ASC', 'DESC'))) {
				$order = $args['order'];
			} else {
				$order = 'DESC';
			}
		} else {
			$order = 'DESC';
		}

		if (isset($args['limit'])) {
			$limit = ' LIMIT ' . $args['limit'];
		} else {
			$limit = '';
		}

		$sql = '
			SELECT
				e.*
			FROM
				' . \Podlove\Model\Episode::table_name() . ' e
				INNER JOIN ' . $wpdb->posts . ' p ON e.post_id = p.ID
			WHERE ' . $where . '
			ORDER BY ' . $orderby . ' ' . $order . 
			$limit;

		$rows = $wpdb->get_results($sql);

		if (!$rows)
			return array();
		
		$episodes = array();
		foreach ($rows as $row) {
			$episode = new \Podlove\Model\Episode();
			$episode->flag_as_not_new();
			foreach ( $row as $property => $value ) {
				$episode->$property = $value;
			}
			if ( $blog_id )
				$episode->blog_id = $blog_id;
			$episodes[] = $episode;
		}

		// filter out invalid episodes
		$episodes = array_filter($episodes, function($e) {
			return $e->is_valid();
		});

		if ( $blog_id )
			restore_current_blog();

		return array_map(function ($episode) {
			return new Episode($episode);
		}, $episodes);		
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