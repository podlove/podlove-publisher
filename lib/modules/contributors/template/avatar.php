<?php
namespace Podlove\Modules\Contributors\Template;

use Podlove\Template\Wrapper;

/**
 * Contributor Avatar Template Wrapper
 *
 * Requires the "Contributor" module.
 *
 * @deprecated since 2.2.0
 * @templatetag avatar
 */
class Avatar extends Wrapper {

	private $contributor;
	
	public function __construct($contributor) {
		$this->contributor = $contributor;
	}

	protected function getExtraFilterArgs() {
		return array($this->contributor);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * Avatar image URL
	 *
	 * Dimensions default to 50x50px.
	 * Change it via parameter: `avatar.url(32)`
	 * 
	 * @accessor
	 */
	public function url($size = 50) {
		return $this->contributor->avatar()->setWidth($size)->url();
	}

}