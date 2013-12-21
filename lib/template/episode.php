<?php
namespace Podlove\Template;

/**
 * Episode Template Wrapper
 *
 * @templatetag episode
 */
class Episode {

	/**
	 * @var Podlove\Model\Episode
	 */
	private $episode;

	/**
	 * @var WP_Post
	 */
	private $post;

	public function __construct(\Podlove\Model\Episode $episode) {
		$this->episode = $episode;
		$this->post = get_post($episode->post_id);

	}

	// /////////
	// Accessors
	// /////////

	public function title() {
		return $this->post->post_title;
	}

	public function subtitle() {
		return $this->episode->subtitle;
	}

	public function summary() {
		return $this->episode->summary;
	}

	public function slug() {
		return $this->episode->slug;
	}

	public function content() {
		return $this->post->post_content;
	}

	public function publicationDate($format = '') {

		if ($format === '')
			$format = get_option('date_format');

		return mysql2date($format, $this->post->post_date);
	}

	/**
	 * Explicit status
	 *
	 * "yes", "no" or "clean"
	 * 
	 * @accessor
	 */
	public function explicit() {
		return $this->episode->explicitText();
	}

	/**
	 * Episode URL
	 * 
	 * @accessor
	 */
	public function url() {
		return get_permalink($this->post->ID);
	}

	/**
	 * Episode duration
	 *
	 * Use `duration("full")` to include milliseconds.
	 *
	 * @todo  support custom formatstrings
	 * @accessor
	 */
	public function duration($format = 'HH:MM:SS') {
		return $this->episode->get_duration($format);
	}

	public function imageUrl() {
		return $this->episode->get_cover_art();
	}

	public function imageUrlWithFallback() {
		return $this->episode->get_cover_art_with_fallback();
	}

	/**
	 * Access a single meta value
	 * 
	 * @accessor
	 */
	public function meta($meta_key) {
		return get_post_meta($this->post->ID, $meta_key, true);
	}

	/**
	 * Access a list of meta values
	 *
	 * Example:
	 *
	 * ```html
	 * <ul>
	 *   {% for meta in episode.metas("mymetakey") %}
	 *     <li>{{ meta }}</li>
	 *   {% endfor %}
	 * </ul>
	 *
	 * {% for meta in episode.metas("mymetakey") %}
	 *   {{ meta }}{% if not loop.last %}, {% endif %}
	 * {% endfor %}
	 * ```
	 *   
	 * @accessor
	 */
	public function metas($meta_key) {
		return get_post_meta($this->post->ID, $meta_key, false);
	}

	/**
	 * List of episode files
	 *
	 * @see  file
	 * @accessor
	 */
	public function files() {
		return array_map(function($file) {
			return new File($file);
		}, $this->episode->media_files());
	}

	/**
	 * List of episode contributors
	 *
	 * @see  contributor
	 * @FIXME this will break without contributor module
	 * @accessor
	 */
	public function contributors() {
		return array_map(function($contribution) {
			return new Contributor($contribution->getContributor(), $contribution);
		}, \Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($this->episode->id));
	}

	/**
	 * List of episode chapters
	 *
	 * @see  chapter
	 * @accessor
	 */
	public function chapters() {
		return array_map(function($chapter) {
			return new Chapter($chapter);
		}, $this->episode->get_chapters()->toArray());
	}

	/**
	 * Episode license
	 * 
	 * @see  license
	 * @accessor
	 */
	public function license() {
		return new License(
			new \Podlove\Model\License(
				"episode",
				array(
					'type'                 => $this->episode->license_type,
					'license_name'         => $this->episode->license_name,
					'license_url'          => $this->episode->license_url,
					'allow_modifications'  => $this->episode->license_cc_allow_modifications,
					'allow_commercial_use' => $this->episode->license_cc_allow_commercial_use,
					'jurisdiction'         => $this->episode->license_cc_license_jurisdiction,
				)
			)
		);
	}

}