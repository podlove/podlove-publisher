<?php
namespace Podlove\Cache;

use \Podlove\Model;

/**
 * Template Caching
 *
 * API to cache rendered text strings.
 * This cache does *not* expire by time. Instead, purging is also handled by
 * this class. Whenever a view-relevant model changes, *all* caches are purged.
 * To register a model class for purging, the 'podlove_cache_tainting_classes'
 * filter has to be used. Look into the source below for usage. Alternatively,
 * a purge can be started manually:
 *
 * 	\Podlove\Cache\TemplateCache::get_instance()->setup_purge();
 *
 * Usage example:
 *
 * 	$cache = \Podlove\Cache\TemplateCache::get_instance();
 * 	$html = $cache->cache_for('unique_cache_key', function() {
 * 		return "Hello World"; // or probably something more costly to generate
 * 	});
 *
 * To globally deactivate caching, put this in the wp-config.php:
 *
 * 	define('PODLOVE_TEMPLATE_CACHE', false);
 */
class TemplateCache {

	private static $instance = NULL;

	/**
	 * If the cache is tainted, it has to be purged.
	 * @var boolean
	 */
	private $is_tainted = false;

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

	protected function __construct()
	{
		register_shutdown_function( array( $this, 'maybe_purge' ) );
		add_action('podlove_purge_template_cache', array($this, 'purge'));
		add_action('podlove_model_change', array($this, 'handle_model_change'));
	}

	final private function __clone() { }

	public static function is_enabled() {
		return !defined('PODLOVE_TEMPLATE_CACHE') || PODLOVE_TEMPLATE_CACHE;
	}

	public function handle_model_change($model)
	{
		$tainting_classes = array(
			Model\Episode::name(),
			Model\Feed::name(),
			// MediaFile is troublesome because it is saved on validation;
			// but thinking about it: it shouldn't affect cache anyway ... ?
			// Model\MediaFile::name(),
			Model\Podcast::name(),
			Model\Template::name(),
			Model\TemplateAssignment::name()
		);

		$tainting_classes = apply_filters('podlove_cache_tainting_classes', $tainting_classes);

		if (in_array($model::name(), $tainting_classes))
			$this->taint();
	}

	public function taint() {
		$this->is_tainted = true;
	}

	public function maybe_purge() {
		if ($this->is_tainted) {
			$this->setup_purge();
		}
	}

	/**
	 * Schedule async cache purge *now*
	 *
	 * Note: time parameter must be set to make the event unique. Otherwise 
	 * WordPress will only execute one purge every 10 minutes.
	 */
	public function setup_purge() {
		wp_schedule_single_event( time(), 'podlove_purge_template_cache', array('time' => time()) );
	}

	public function purge() {
		$cache_keys_string = get_option('podlove_tpl_cache_keys', '');
		$keys = explode(",", $cache_keys_string);
		foreach ($keys as $cache_key) {
			delete_transient($cache_key);
		}
		update_option('podlove_tpl_cache_keys', '');
	}

	/**
	 * Fetch and/or fill cache for given key.
	 * 
	 * @param  string   $cache_key Must be unique for the given template and context.
	 * @param  function $callback  The function that generates the content in case of a cache miss.
	 * @return string              Content for given cache key.
	 */
	public function cache_for($cache_key, $callback)
	{
		if (!self::is_enabled())
			return call_user_func($callback);

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