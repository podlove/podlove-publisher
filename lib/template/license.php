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
	 * Is this a creative commons license?
	 * 
	 * @accessor
	 */
	public function creativeCommons() {
		return $this->license->isCreativeCommons();
	}

	/**
	 * Is the license valid? Is all required data available?
	 * 
	 * @accessor
	 */
	public function valid() {
		return $this->creativeCommons() && $this->license->hasCompleteCCData()
		    || !$this->creativeCommons() && $this->license->hasCompleteOtherData();
	}

	/**
	 * HTML
	 *
	 * @deprecated use `{% include '@core/license.twig' %}` instead
	 * @accessor
	 */
	public function html() {
		return $this->license->getHtml();
	}

}