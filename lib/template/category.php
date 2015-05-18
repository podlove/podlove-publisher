<?php
namespace Podlove\Template;

/**
 * Category Template Wrapper
 *
 * @templatetag category
 */
class Category extends Wrapper {

	use \Podlove\Model\KeepsBlogReferenceTrait;

	private $category;

	public function __construct($category, $blog_id = null) {
		$this->category = $category;
		$this->set_blog_id($blog_id);
	}

	protected function getExtraFilterArgs() {
		return array($this->category);
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
		return $this->category->term_id;
	}

	/**
	 * Term Name
	 * 
	 * @accessor
	 */
	public function name() {
		return $this->category->name;
	}

	/**
	 * Term Slug
	 * 
	 * @accessor
	 */
	public function slug() {
		return $this->category->slug;
	}

	/**
	 * Term Description
	 * 
	 * @accessor
	 */
	public function description() {
		return $this->category->description;
	}

	/**
	 * Term Count
	 * 
	 * @accessor
	 */
	public function count() {
		return $this->category->count;
	}

	/**
	 * Term URL
	 * 
	 * @accessor
	 */
	public function url() {
		return $this->with_blog_scope(function() {
			return get_category_link($this->category->term_id);
		});
	}

}