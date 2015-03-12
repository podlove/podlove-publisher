<?php
namespace Podlove\Model;

/**
 * Keep reference to a blog id.
 * 
 * Example usage:
 * 
 * class MyModel {
 *   use KeepsBlogReferenceTrait;
 * 
 *   public function __construct() { $this->set_blog_id(); }
 * }
 */
trait KeepsBlogReferenceTrait {

	private $blog_id = NULL;

	public function set_blog_id($blog_id = NULL) {
		$this->blog_id = $blog_id !== NULL ? $blog_id : get_current_blog_id();
	}

	public function get_blog_id() {
		return $this->blog_id;
	}

}