<?php
namespace Podlove\Template;

class Asset {

	/**
	 * @var Podlove\Model\EpisodeAsset
	 */
	private $asset;

	public function __construct(\Podlove\Model\EpisodeAsset $asset) {
		$this->asset = $asset;
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * Asset title
	 * 
	 * @accessor
	 */
	public function title() {
		return $this->asset->title;
	}

	/**
	 * Is the asset downloadable?
	 * 
	 * @accessor
	 */
	public function downloadable() {
		return (bool) $this->asset->downloadable;
	}

	/**
	 * Asset file type
	 * 
	 * @see  file_type
	 * @accessor
	 */
	public function fileType() {
		return new FileType($this->asset->file_type());
	}

}