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
		// @todo generate warning if a shortcode is used in subtitles
		return \Podlove\PHP\escape_shortcodes($this->episode->subtitle);
	}

	/**
	 * Summary
	 * 
	 * @accessor
	 */
	public function summary() {
		// @todo generate warning if a shortcode is used in summaries
		return \Podlove\PHP\escape_shortcodes($this->episode->summary);
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
	 * Podcast
	 * 
	 * @accessor
	 */
	public function podcast() {
		return new \Podlove\Template\Podcast(
			\Podlove\Model\Podcast::get($this->episode->get_blog_id())
		);
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
	 * You can set a custom context for tracking:
	 * 
	 * ```jinja
	 * {{ episode.player({context: 'landing-page'}) }}
	 * ```
	 * 
	 * @accessor
	 */
	public function player($args = []) {

		$defaults = ['context' => NULL];
		$args = wp_parse_args($args, $defaults);

		return $this->episode->player($args['context']);
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
	 * WordPress WP_Post object
	 * 
	 * @accessor
	 */
	public function post() {
		return $this->post;
	}

	/**
	 * Image
	 * 
	 * - fallback: `true` or `false`. Should the podcast image be used if no episode image is available? Default: `false`
	 * 
	 * Example:
	 * 
	 * ```jinja
	 * {{ episode.image({fallback: true}).url }}
	 * ```
	 * 
	 * @see  image
	 * @accessor
	 */
	public function image($args = []) {

		$defaults = ['fallback' => false];
		$args = wp_parse_args($args, $defaults);

		if ($args['fallback']) {
			return new Image($this->episode->cover_art_with_fallback());
		} else {
			if ($cover_art = $this->episode->cover_art())
				return new Image($cover_art);
			else
				return '';
			
		}
	}

	/**
	 * Image URL
	 * 
	 * @deprecated since 2.2.0, use `episode.image.url` instead
	 * @accessor
	 */
	public function imageUrl() {

		if ($cover_art = $this->episode->cover_art())
			return new Image($cover_art);

		return '';
	}

	/**
	 * Image URL with fallback
	 *
	 * @deprecated since 2.2.0, use `episode.image({fallback: true}).url` instead
	 * @accessor
	 */
	public function imageUrlWithFallback() {
		return new Image($this->episode->cover_art_with_fallback());
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
	 * @see  tag
	 * @accessor
	 */
	public function tags($args = []) {
		return array_map(function($tag) {
			return new Tag($tag, $this->episode->get_blog_id());
		}, $this->episode->tags($args));
	}

	/**
	 * Access a list of episode categories.
	 *
	 * See http://codex.wordpress.org/Function_Reference/wp_get_object_terms#Argument_Options
	 * for a list of available argument options.
	 *
	 * Requires the "Categories" module.
	 * 
	 * Example:
	 *
	 * ```html
	 *   {% for category in episode.categories({order: "ASC", orderby: "count"}) %}
	 *     <a href="{{ category.url }}">{{ category.name }} ({{ category.count }})</a>
	 *   {% endfor %}
	 * ```
	 * 
	 * @see  category
	 * @accessor
	 */
	public function categories($args = []) {
		return array_map(function($category) {
			return new Category($category, $this->episode->get_blog_id());
		}, $this->episode->categories($args));
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
		$chapters = $this->episode->get_chapters();

		if (!$chapters)
			return [];

		return array_map(function($chapter) {
			return new Chapter($chapter);
		}, $chapters->toArray());
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