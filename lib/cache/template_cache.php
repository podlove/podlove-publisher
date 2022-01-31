<?php

namespace Podlove\Cache;

use Podlove\Model;

/**
 * Template Caching.
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
class TemplateCache
{
    const CACHE_NAMESPACE = 'podlove_cachev2_';
    const CRON_PURGE_HOOK = 'podlove_purge_template_cache';
    private static $instance;

    /**
     * If the cache is tainted, it has to be purged.
     *
     * @var bool
     */
    private $is_tainted = false;

    protected function __construct()
    {
        register_shutdown_function([$this, 'maybe_purge']);
        add_action(self::CRON_PURGE_HOOK, [$this, 'purge']);
        add_action('podlove_model_change', [$this, 'handle_model_change']);
    }

    private function __clone()
    {
    }

    /**
     * Singleton.
     *
     * @return TemplateCache
     */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function is_enabled()
    {
        return !defined('PODLOVE_TEMPLATE_CACHE') || PODLOVE_TEMPLATE_CACHE;
    }

    public function handle_model_change($model)
    {
        $tainting_classes = [
            Model\Episode::name(),
            Model\Feed::name(),
            // MediaFile is troublesome because it is saved on validation;
            // but thinking about it: it shouldn't affect cache anyway ... ?
            // Model\MediaFile::name(),
            Model\Podcast::name(),
            Model\Template::name(),
            Model\TemplateAssignment::name(),
        ];

        $tainting_classes = apply_filters('podlove_cache_tainting_classes', $tainting_classes);

        if (in_array($model::name(), $tainting_classes)) {
            $this->taint();
        }
    }

    public function taint()
    {
        $this->is_tainted = true;
    }

    public function maybe_purge()
    {
        if ($this->is_tainted) {
            $this->setup_purge();
        }
    }

    /**
     * Schedule async cache purge *now*.
     */
    public function setup_purge()
    {
        if (!wp_next_scheduled(self::CRON_PURGE_HOOK)) {
            wp_schedule_single_event(time(), self::CRON_PURGE_HOOK);
        }
    }

    /**
     * Setup cache purge in all blogs.
     */
    public function setup_purge_in_all_blogs()
    {
        global $wpdb;

        \Podlove\for_every_podcast_blog(function () {
            TemplateCache::get_instance()->setup_purge();
        });
    }

    /**
     * Setup complete purge, depending on if we are a Multisite.
     */
    public function setup_global_purge()
    {
        if (is_multisite()) {
            TemplateCache::get_instance()->setup_purge_in_all_blogs();
        } else {
            TemplateCache::get_instance()->setup_purge();
        }
    }

    /**
     * Purge all caches.
     *
     * @todo Purging cache by DELETE query works for DB-storage only.
     * In previous versions, I memorized all generated cache keys but that
     * lead to its own set of problems (race conditions, db locks because
     * it's a huge value that is written to very often, ...).
     * I would prefer either a `delete_all_transients()` or `delete_transient_matching(<string>)`
     * method. However, WordPress only lets you delete by exact key.
     *
     * That's why, at the moment, purging only works for DB storage (which is the default).
     * Other caches expire automatically after 24 hours.
     */
    public function purge()
    {
        global $wpdb;

        // quick, reliable purge (but only works with database as backend)
        $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE \"_transient_".self::CACHE_NAMESPACE.'%"';
        $wpdb->query($sql);
        $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE \"_transient_timeout_".self::CACHE_NAMESPACE.'%"';
        $wpdb->query($sql);
    }

    /**
     * Fetch and/or fill cache for given key.
     *
     * @param string   $cache_key  must be unique for the given template and context
     * @param function $callback   the function that generates the content in case of a cache miss
     * @param mixed    $expiration
     *
     * @return string content for given cache key
     */
    public function cache_for($cache_key, $callback, $expiration = DAY_IN_SECONDS)
    {
        if (!self::is_enabled()) {
            return call_user_func($callback);
        }

        $cache_key = $this->generate_cache_key($cache_key);

        if (($html = get_transient($cache_key)) !== false) {
            return $html;
        }
        $html = call_user_func($callback);

        if ($html !== false) {
            set_transient($cache_key, $html, $expiration);
        }

        return $html;
    }

    public function delete_cache_for($cache_key)
    {
        $cache_key = $this->generate_cache_key($cache_key);

        return delete_transient($cache_key);
    }

    public function expiration_for($cache_key)
    {
        $cache_key = $this->generate_cache_key($cache_key);

        return get_option('_transient_timeout_'.$cache_key);
    }

    private function memorize_cache_key($cache_key)
    {
        $cache_keys = get_option('podlove_tpl_cache_keys', '');

        if (strlen($cache_keys)) {
            $cache_keys .= ','.$cache_key;
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
     *  Transients API prepends "_transient_timeout_", 19 characters
     *  64 - 19 = 45 (minus one because you never know)
     *
     * @param mixed $cache_key
     *
     * @return string
     */
    private function generate_cache_key($cache_key)
    {
        $cache_key = sprintf('%s%s', self::CACHE_NAMESPACE, sha1($cache_key));

        return substr($cache_key, 0, 44);
    }
}
