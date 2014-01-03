<?php
namespace Podlove\Template;

/**
 * Chapter Template Wrapper
 * 
 * @templatetag chapter
 */
class Chapter extends Wrapper {

	/**
	 * @var \Podlove\Chapters\Chapter
	 */
	private $chapter;

	public function __construct(\Podlove\Chapters\Chapter $chapter) {
		$this->chapter = $chapter;
	}

	protected function getExtraFilterArgs() {
		return array($this->chapter);
	}
	
	// /////////
	// Accessors
	// /////////

	/**
	 * Chapter title
	 * 
	 * @accessor
	 */
	public function title() {
		return $this->chapter->get_title();
	}

	/**
	 * Chapter link
	 * 
	 * @accessor
	 */
	public function link() {
		return $this->chapter->get_link();
	}

	/**
	 * Chapter image
	 * 
	 * @accessor
	 */
	public function image() {
		return $this->chapter->get_image();
	}

	/**
	 * Chapter time
	 * 
	 * @accessor
	 */
	public function time() {
		return $this->chapter->get_time();
	}
}