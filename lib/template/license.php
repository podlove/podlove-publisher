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

	/**
	 * License name
	 *
	 * @accessor
	 */
	public function name() {
		return $this->license->getName();
	}

	/**
	 * License url
	 *
	 * @accessor
	 */
	public function url() {
		return $this->license->getUrl();
	}

	/**
	 * License image URL
	 *
	 * @accessor
	 */
	public function imageUrl() {
		return $this->license->getPictureUrl();
	}

	/**
	 * License HTML
	 *
	 * @accessor
	 */
	public function html() {
		return $this->license->getHtml();
	}

}