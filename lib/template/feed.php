<?php
namespace Podlove\Template;

class Feed {

	/**
	 * @var Podlove\Model\Feed
	 */
	private $feed;

	public function __construct(\Podlove\Model\Feed $feed) {
		$this->feed = $feed;
	}

	// /////////
	// Accessors
	// /////////

	public function title() {
		if ($this->feed->title) {
			return $this->feed->title;
		} else {
			return $this->feed->title_for_discovery();
		}
	}

	public function url() {
		return $this->feed->get_subscribe_url();
	}


}