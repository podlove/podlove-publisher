<?php
namespace Podlove\Template;

/**
 * Feed Template Wrapper
 *
 * @templatetag feed
 */
class Feed extends Wrapper {

	/**
	 * @var Podlove\Model\Feed
	 */
	private $feed;

	public function __construct(\Podlove\Model\Feed $feed) {
		$this->feed = $feed;
	}

	protected function getExtraFilterArgs() {
		return array($this->feed);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * Feed title
	 * 
	 * @accessor
	 */
	public function title() {
		if ($this->feed->title) {
			return $this->feed->title;
		} else {
			return $this->feed->title_for_discovery();
		}
	}

	/**
	 * Feed url
	 * 
	 * @accessor
	 */
	public function url() {
		return $this->feed->get_subscribe_url();
	}

	/**
	 * Is the feed URL discoverable?
	 * 
	 * @accessor
	 */
	public function discoverable() {
		return (bool) $this->feed->discoverable;
	}

	/**
	 * Is the feed protected by a password?
	 * 
	 * @accessor
	 */
	public function passwordProtected() {
		return (bool) $this->feed->protected;
	}

	/**
	 * Feed asset
	 *
	 * @see asset
	 * @accessor
	 */
	public function asset() {
		return new Asset($this->feed->episode_asset());
	}

}