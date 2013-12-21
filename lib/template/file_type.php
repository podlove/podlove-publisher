<?php
namespace Podlove\Template;

class FileType {

	/**
	 * @var Podlove\Model\FileType
	 */
	private $fileType;

	public function __construct(\Podlove\Model\FileType $fileType) {
		$this->fileType = $fileType;
	}

	// /////////
	// Accessors
	// /////////

	public function name() {
		return $this->fileType->name;
	}

	public function type() {
		return $this->fileType->type;
	}

	public function mimeType() {
		return $this->fileType->mime_type;
	}

	public function extension() {
		return $this->fileType->extension;
	}

}