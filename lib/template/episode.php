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

	public function content() {
		return $this->post->post_content;
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
	 * This is the duration of an episode.
	 *
	 * 	- foo
	 * 	- bar
	 * 
	 * @accessor
	 * @accessor2 asd
	 */
	public function duration() {
		return $this->episode->get_duration();
	}

	/**
	 * @FIXME this will break without contributor module
	 */
	public function contributors() {
		return array_map(function($contribution) {
			return new Contributor($contribution->getContributor(), $contribution);
		}, \Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($this->episode->id));
	}

}