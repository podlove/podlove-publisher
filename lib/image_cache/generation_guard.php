<?php

namespace Podlove\ImageCache;

use Podlove\Model\Image;

class GenerationGuard
{
    private $key;
    private $lock_file;
    private $locked = false;

    public function __construct($key)
    {
        $this->key = preg_replace('/[^a-f0-9]/', '', strtolower((string) $key));
        $this->lock_file = trailingslashit($this->lock_dir()).$this->key.'.lock';
    }

    public function __destruct()
    {
        $this->release();
    }

    public function acquire()
    {
        if ($this->locked) {
            return true;
        }

        if (!wp_mkdir_p($this->lock_dir())) {
            return false;
        }

        $handle = @fopen($this->lock_file, 'x');
        if (false === $handle && $this->lock_is_stale()) {
            wp_delete_file($this->lock_file);
            $handle = @fopen($this->lock_file, 'x');
        }

        if (false === $handle) {
            return false;
        }

        fwrite($handle, (string) time());
        fclose($handle);
        $this->locked = true;

        return true;
    }

    public function release()
    {
        if (!$this->locked) {
            return;
        }

        wp_delete_file($this->lock_file);
        $this->locked = false;
    }

    public function is_backed_off()
    {
        $failure = $this->read_failure();

        return isset($failure['retry_after']) && (int) $failure['retry_after'] > time();
    }

    public function record_failure()
    {
        if (!wp_mkdir_p($this->failure_dir())) {
            return;
        }

        $failure = $this->read_failure();
        $count = min(5, max(0, (int) ($failure['count'] ?? 0)) + 1);
        $delay = min(HOUR_IN_SECONDS, 5 * MINUTE_IN_SECONDS * (2 ** ($count - 1)));

        file_put_contents(
            $this->failure_file(),
            wp_json_encode([
                'count' => $count,
                'retry_after' => time() + $delay,
            ]),
            LOCK_EX
        );
    }

    public function clear_failure()
    {
        if (file_exists($this->failure_file())) {
            wp_delete_file($this->failure_file());
        }
    }

    private function lock_dir()
    {
        return trailingslashit(Image::cache_dir()).'.locks';
    }

    private function failure_dir()
    {
        return trailingslashit(Image::cache_dir()).'.failures';
    }

    private function failure_file()
    {
        return trailingslashit($this->failure_dir()).$this->key.'.json';
    }

    private function lock_is_stale()
    {
        return file_exists($this->lock_file)
            && filemtime($this->lock_file) < time() - 2 * MINUTE_IN_SECONDS;
    }

    private function read_failure()
    {
        if (!file_exists($this->failure_file())) {
            return [];
        }

        $contents = file_get_contents($this->failure_file());
        if (false === $contents) {
            return [];
        }

        $failure = json_decode($contents, true);

        return is_array($failure) ? $failure : [];
    }
}
