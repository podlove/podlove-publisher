<?php
namespace Podlove\Template;

/**
 * File Template Wrapper
 *
 * @templatetag file
 */
class File extends Wrapper {

	/**
	 * @var Podlove\Model\MediaFile
	 */
	private $file;

	public function __construct(\Podlove\Model\MediaFile $file) {
		$this->file = $file;
	}

	protected function getExtraFilterArgs() {
		return array($this->file);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * File id
	 * 
	 * @accessor
	 */
	public function id() {
		return $this->file->id;
	}

	/**
	 * Episode related to this file
	 *
	 * @see  episode
	 * @accessor
	 */
	public function episode() {
		return new Episode($this->file->episode());
	}

	/**
	 * Asset related to this file
	 *
	 * @see  asset
	 * @accessor
	 */
	public function asset() {
		return new Asset($this->file->episode_asset());
	}

	/**
	 * Size in bytes
	 *
	 * @accessor
	 */
	public function size() {
		return $this->file->size;
	}

	/**
	 * URL
	 *
	 * @accessor
	 */
	public function url() {
		return $this->file->get_file_url();
	}

	public function publicUrl($source, $context = null) {
		return $this->file->get_public_file_url($source, $context);
	}

}