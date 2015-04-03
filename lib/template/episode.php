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
		$this->post    = $episode->post();
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

	/**
	 * Web Player for the current episode
	 * 
	 * The player should not appear in feeds, so embed it like this:
	 * 
	 * ```jinja
	 * {% if not is_feed() %}
	 *   {{ episode.player }}
	 * {% endif %}
	 * ```
	 * 
	 * @accessor
	 */
	public function player() {
		return $this->episode->player();
	}

	/**
	 * Post publication date
	 *
	 * Uses WordPress datetime format by default or custom format: `{{ episode.publicationDate.format('Y-m-d') }}`
	 *
	 * @see  datetime
	 * @accessor
	 */
	public function publicationDate($format = '') {
		return new \Podlove\Template\DateTime(strtotime($this->post->post_date), $format);
	}

	/**
	 * Post recording date
	 *
	 * Uses WordPress datetime format by default or custom format: `{{ episode.recordingDate.format('Y-m-d') }}`
	 *
	 * @see  datetime
	 * @accessor
	 */
	public function recordingDate($format = '') {
		return new \Podlove\Template\DateTime(strtotime($this->episode->recording_date), $format);
	}

	/**
	 * Explicit status
	 *
	 * "yes", "no" or "clean"
	 * 
	 * @accessor
	 */
	public function explicit() {
		return $this->episode->explicit_text();
	}

	/**
	 * URL
	 * 
	 * @accessor
	 */
	public function url() {
		return $this->episode->permalink();
	}

	/**
	 * Duration Object
	 *
	 * Use `duration` to display formatted hours, minutes and seconds.
	 * Alternatively, use the duration accessors for custom rendering.
	 *
	 * @see duration
	 * @accessor
	 */
	public function duration() {
		return new Duration($this->episode);
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
		return $this->episode->meta($meta_key, true);
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
		return $this->episode->meta($meta_key, false);
	}

	/**
	 * Access a list of post tags.
	 *
	 * See http://codex.wordpress.org/Function_Reference/wp_get_object_terms#Argument_Options
	 * for a list of available argument options.
	 *
	 * Example:
	 *
	 * ```html
	 *   {% for tag in episode.tags({order: "ASC", orderby: "count"}) %}
	 *     <a href="{{ tag.url }}">{{ tag.name }} ({{ tag.count }})</a>
	 *   {% endfor %}
	 * ```
	 * 
	 * @accessor
	 */
	public function tags($args = []) {
		return array_map(function($tag) {
			return new Tag($tag);
		}, $this->episode->tags($args));
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
	 * To render an HTML license, use `{% include '@core/license.twig' %}` for
	 * a license with fallback to the podcast license or 
	 * `{% include '@core/license.twig' with {'license': episode.license} %}`
	 * for the episode license only.
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