<?php
namespace Podlove\Template;

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

	public function chapters() {
		return array_map(function($chapter) {
			return new Chapter($chapter);
		}, $this->episode->get_chapters()->toArray());
	}

	/**
	 * Explicit Status.
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
	 * Episode Duration
	 *
	 * Use duration("full") to include milliseconds.
	 * 
	 * @accessor
	 */
	public function duration($format = 'HH:MM:SS') {
		return $this->episode->get_duration($format);
	}

	/**
	 * List of episode contributors
	 * 
	 * @FIXME this will break without contributor module
	 * @accessor
	 */
	public function contributors() {
		return array_map(function($contribution) {
			return new Contributor($contribution->getContributor(), $contribution);
		}, \Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($this->episode->id));
	}

}