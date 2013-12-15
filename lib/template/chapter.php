<?php
namespace Podlove\Template;

class Chapter {

	/**
	 * @var \Podlove\Chapters\Chapter
	 */
	private $chapter;

	public function __construct(\Podlove\Chapters\Chapter $chapter) {
		$this->chapter = $chapter;

	}

	// /////////
	// Accessors
	// /////////

	public function title() {
		return $this->chapter->get_title();
	}

	public function link() {
		return $this->chapter->get_link();
	}

	public function image() {
		return $this->chapter->get_image();
	}

	public function time() {
		return $this->chapter->get_time();
	}
}