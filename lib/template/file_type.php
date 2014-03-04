<?php
namespace Podlove\Template;

/**
 * Filetype Template Wrapper
 *
 * @templatetag file_type
 */
class FileType extends Wrapper {

	/**
	 * @var Podlove\Model\FileType
	 */
	private $fileType;

	public function __construct(\Podlove\Model\FileType $fileType) {
		$this->fileType = $fileType;
	}

	protected function getExtraFilterArgs() {
		return array($this->fileType);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * Name
	 *
	 * @accessor
	 */
	public function name() {
		return $this->fileType->name;
	}

	/**
	 * Type / group
	 *
	 * One of those: audio, captions, chapters, ebook, image, metadata, video
	 *
	 * @accessor
	 */
	public function type() {
		return $this->fileType->type;
	}
	
	/**
	 * Mimetype
	 *
	 * @accessor
	 */
	public function mimeType() {
		return $this->fileType->mime_type;
	}

	/**
	 * Extension
	 *
	 * @accessor
	 */
	public function extension() {
		return $this->fileType->extension;
	}

}