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
trait KeepsBlogReferenceTrait
{
    private $blog_id;

    public function set_blog_id($blog_id = null)
    {
        $this->blog_id = $blog_id !== null ? $blog_id : get_current_blog_id();
    }

    public function get_blog_id()
    {
        return $this->blog_id;
    }

    public function with_blog_scope($callback)
    {
        $result = null;

        if ($this->blog_id != get_current_blog_id()) {
            switch_to_blog($this->blog_id);
            $result = $callback();
            restore_current_blog();
        } else {
            $result = $callback();
        }

        return $result;
    }
}
