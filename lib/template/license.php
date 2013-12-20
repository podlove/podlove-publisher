<?php
namespace Podlove\Template;

class License {

	/**
	 * @var Podlove\Model\License
	 */
	private $license;

	public function __construct(\Podlove\Model\License $license) {
		$this->license = $license;
	}

	// /////////
	// Accessors
	// /////////

	public function name() {
		return $this->license->getName();
	}

	public function url() {
		return $this->license->getUrl();
	}

	public function imageUrl() {
		return $this->license->getPictureUrl();
	}

	public function html() {
		return $this->license->getHtml();
	}

}