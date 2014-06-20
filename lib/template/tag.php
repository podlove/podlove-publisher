<?php
namespace Podlove\Template;

/**
 * File Template Wrapper
 *
 * @templatetag tag
 */
class Tag extends Wrapper {

	private $tag;

	public function __construct($tag) {
		$this->tag = $tag;
	}

	protected function getExtraFilterArgs() {
		return array($this->tag);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * Term id
	 * 
	 * @accessor
	 */
	public function id() {
		return $this->tag->term_id;
	}

	/**
	 * Term Name
	 * 
	 * @accessor
	 */
	public function name() {
		return $this->tag->name;
	}

	/**
	 * Term Slug
	 * 
	 * @accessor
	 */
	public function slug() {
		return $this->tag->slug;
	}

	/**
	 * Term Description
	 * 
	 * @accessor
	 */
	public function description() {
		return $this->tag->description;
	}

	/**
	 * Term Count
	 * 
	 * @accessor
	 */
	public function count() {
		return $this->tag->count;
	}

	/**
	 * Term URL
	 * 
	 * @accessor
	 */
	public function url() {
		return get_tag_link($this->tag->term_id);
	}

}