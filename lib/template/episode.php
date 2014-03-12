<?php
namespace Podlove\Template;

/**
 * Episode Template Wrapper
 *
 * @templatetag episode
 */
class Episode extends Wrapper {

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

	protected function getExtraFilterArgs() {
		return array($this->episode, $this->post);
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
		return $this->post->post_title;
	}

	/**
	 * Subtitle
	 * 
	 * @accessor
	 */
	public function subtitle() {
		return $this->episode->subtitle;
	}

	/**
	 * Summary
	 * 
	 * @accessor
	 */
	public function summary() {
		return $this->episode->summary;
	}

	/**
	 * Slug
	 * 
	 * @accessor
	 */
	public function slug() {
		return $this->episode->slug;
	}

	/**
	 * Post content
	 * 
	 * @accessor
	 */
	public function content() {
		return $this->post->post_content;
	}

	public function player() {
		$printer = new \Podlove\Modules\PodloveWebPlayer\Printer( $this->episode );
		return $printer->render();
	}

	/**
	 * Post publication date
	 *
	 * Uses WordPress datetime format by default or custom format: `{{ episode.publicationDate('Y-m-d') }}`
	 * 
	 * @accessor
	 */
	public function publicationDate($format = '') {

		if ($format === '')
			$format = get_option('date_format') . ' ' . get_option('time_format');

		return mysql2date($format, $this->post->post_date);
	}

	/**
	 * Post recording date
	 *
	 * Uses WordPress datetime format by default or custom format: `{{ episode.recordingDate('Y-m-d') }}`
	 *
	 * @accessor
	 */
	public function recordingDate($format = '') {

		if ($format === '')
			$format = get_option('date_format') . ' ' . get_option('time_format');

		return mysql2date($format, $this->episode->recording_date);
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
	 * URL
	 * 
	 * @accessor
	 */
	public function url() {
		return get_permalink($this->post->ID);
	}

	/**
	 * Duration
	 *
	 * Use `duration("full")` to include milliseconds.
	 *
	 * @todo  support custom formatstrings
	 * @accessor
	 */
	public function duration($format = 'HH:MM:SS') {
		return $this->episode->get_duration($format);
	}

	/**
	 * Image URL
	 * 
	 * @accessor
	 */
	public function imageUrl() {
		return $this->episode->get_cover_art();
	}

	/**
	 * Image URL with fallback
	 *
	 * Returns podcast image if no episode image is available.
	 * 
	 * @accessor
	 */
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
	 * License
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