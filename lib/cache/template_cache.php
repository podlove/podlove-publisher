<?php
namespace Podlove\Cache;

class TemplateCache {

	private static $instance = NULL;

	/**
	 * Singleton.
	 * 
	 * @return \Podlove\Model\AssetAssignment
	 */
	static public function get_instance() {

		 if ( ! isset( self::$instance ) )
		     self::$instance = new self;

		 return self::$instance;
	}

	protected function __construct() { }

	final private function __clone() { }

	/**
	 * Fetch and/or fill cache for given key.
	 * 
	 * @param  string   $cache_key Must be unique for the given template and context.
	 * @param  function $callback  The function that generates the content in case of a cache miss.
	 * @return string              Content for given cache key.
	 */
	public function cache_for($cache_key, $callback)
	{
		$cache_key = $this->generate_cache_key($cache_key);

		if (($html = get_transient($cache_key)) !== FALSE) {
			return $html;
		} else {
			
			$html = call_user_func($callback);

			if ($html !== FALSE) {
				set_transient($cache_key, $html);
				$this->memorize_cache_key($cache_key);
			}
				
			return $html;
		}
	}

	private function memorize_cache_key($cache_key)
	{
		$cache_keys = get_option('podlove_tpl_cache_keys', '');

		if (strlen($cache_keys)) {
			$cache_keys .= "," . $cache_key;
		} else {
			$cache_keys = $cache_key;
		}
		update_option('podlove_tpl_cache_keys', $cache_keys);
	}

	/**
	 * Generate a valid cache key.
	 *
	 * - assumes, given $cache_key is unique
	 * - adds "podlove_cache_" as namespace
	 * - apply sha1 because we may have to cut off the end of the key. And if the
	 *   variation only happens in the end of the keys (example: permalinks), that
	 *   would lead to cache collisions. sha1-ing avoids this.
	 * - ensures key is not too long:
	 * 	Cache key must not be longer than 64 characters!
	 *  Transients API prepends "_transient_", 11 characters
	 *  64 - 11 = 53 (minus one because you never know)
	 *
	 * @return string
	 */
	private function generate_cache_key($cache_key) {
		$cache_key = sprintf("podlove_cache_%s", sha1($cache_key));
		return substr($cache_key, 0, 52);
	}

}