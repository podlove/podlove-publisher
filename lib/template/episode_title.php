<?php
namespace Podlove\Template;

/**
 * Episode Title Wrapper
 *
 * @templatetag duration
 */
class EpisodeTitle extends Wrapper {

	private $episode;
	
	public function __construct(\Podlove\Model\Episode $episode) {
		$this->episode = $episode;
	}

	protected function getExtraFilterArgs() {
		return array($this->episode);
	}

	// /////////
	// Accessors
	// /////////

	public function __toString() {
		if ($this->clean()) {
			return $this->clean();
		} else {
			return $this->blog();
		}
	}

	/**
	 * Blog Title
	 * 
	 * The episode title as it appears in the blog.
	 * 
	 * @accessor
	 */
	public function blog() {
		return $this->episode->post_title();
	}

	/**
	 * Feed Title
	 * 
	 * The episode title as it appears in the feed.
	 * 
	 * @accessor
	 */
	public function clean() {
		return $this->episode->title;
	}
}
