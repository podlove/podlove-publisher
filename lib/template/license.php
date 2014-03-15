<?php
namespace Podlove\Template;

/**
 * License Template Wrapper
 *
 * @templatetag license
 */
class License extends Wrapper {

	/**
	 * @var Podlove\Model\License
	 */
	private $license;

	public function __construct(\Podlove\Model\License $license) {
		$this->license = $license;
	}

	protected function getExtraFilterArgs() {
		return array($this->license);
	}

	// /////////
	// Accessors
	// /////////


	/**
	 * Alias for `html` accessor
	 */
	public function __toString() {
		return $this->html();
	}

	/**
	 * Name
	 *
	 * @accessor
	 */
	public function name() {
		return $this->license->getName();
	}

	/**
	 * URL
	 *
	 * @accessor
	 */
	public function url() {
		return $this->license->getUrl();
	}

	/**
	 * Image URL
	 *
	 * @accessor
	 */
	public function imageUrl() {
		return $this->license->getPictureUrl();
	}

	/**
	 * HTML
	 *
	 * @accessor
	 */
	public function html() {
		return $this->license->getHtml();
	}

}