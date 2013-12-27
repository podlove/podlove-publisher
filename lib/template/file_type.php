<?php
namespace Podlove\Template;

/**
 * Filetype Template Wrapper
 *
 * @templatetag file_type
 */
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

	/**
	 * Filetype name
	 *
	 * @accessor
	 */
	public function name() {
		return $this->fileType->name;
	}

	/**
	 * Filetype type / group
	 *
	 * One of those: audio, captions, chapters, ebook, image, metadata, video
	 *
	 * @accessor
	 */
	public function type() {
		return $this->fileType->type;
	}
	
	/**
	 * Filetype Mimetype
	 *
	 * @accessor
	 */
	public function mimeType() {
		return $this->fileType->mime_type;
	}

	/**
	 * Filetype extension
	 *
	 * @accessor
	 */
	public function extension() {
		return $this->fileType->extension;
	}

}