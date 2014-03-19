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
	 * Title
	 * 
	 * @accessor
	 */
	public function title() {
		return $this->chapter->get_title();
	}

	/**
	 * Link
	 * 
	 * @accessor
	 */
	public function link() {
		return $this->chapter->get_link();
	}

	/**
	 * Image
	 * 
	 * @accessor
	 */
	public function image() {
		return $this->chapter->get_image();
	}

	/**
	 * Time
	 * 
	 * @accessor
	 */
	public function time() {
		return $this->chapter->get_time();
	}
}