<?php
namespace Podlove\Cache;

/**
 * Checks HTTP header of URL for changrs in etag or last_modified.
 * 
 * Usage
 * 
 * 	$validator = new HttpHeaderValidator('http://example.com/resource.jpg', $etag, $last_modified);
 * 	$validator->validate();
 * 	if ($validator->hasChanged()) {
 * 		// etag or last_modified have changed
 * 	}
 */
class HttpHeaderValidator {

	private $url;
	private $etag;
	private $last_modified;

	private $has_changed = false;

	public function __construct($url, $etag = NULL, $last_modified = NULL) {
		$this->url           = $url;
		$this->etag          = $etag;
		$this->last_modified = $last_modified;
	}

	public function validate() {
		
		$response = wp_remote_head($this->url);

		// Might just be unavailable right now, so ignore.
		// It would be great to track this over time and create conflicts.
		if (is_wp_error($response))
			return;

		$remote_etag          = wp_remote_retrieve_header($response, 'etag');
		$remote_last_modified = wp_remote_retrieve_header($response, 'last-modified');

		if ($this->etag || $remote_etag) 
			if ($this->etag != $remote_etag)
				$this->has_changed = true;

		if ($this->last_modified || $remote_last_modified)
			if ($this->last_modified != $remote_last_modified)
				$this->has_changed = true;

		// @fixme: what to do if both etag and last_modified are missing?
		// right now those cases never count as "changed"
	}

	public function hasChanged() {
		return $this->has_changed;
	}

}