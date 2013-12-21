<?php
namespace Podlove\Template;

class File {

	/**
	 * @var Podlove\Model\MediaFile
	 */
	private $file;

	public function __construct(\Podlove\Model\MediaFile $file) {
		$this->file = $file;
	}

	// /////////
	// Accessors
	// /////////

	public function episode() {
		return new Episode($this->file->episode());
	}

	public function asset() {
		return new Asset($this->file->episode_asset());
	}

	public function size() {
		return $this->file->size;
	}

	public function url() {
		return $this->file->get_file_url();
	}

}