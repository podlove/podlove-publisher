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

	public function title() {
		return $this->asset->title;
	}

	public function downloadable() {
		return (bool) $this->asset->downloadable;
	}

	public function fileType() {
		return new FileType($this->asset->file_type());
	}

}