<?php
namespace Podlove\Modules\Contributors\Template;

use Podlove\Template\Wrapper;

/**
 * Contributor Avatar Template Wrapper
 *
 * Requires the "Contributor" module.
 *
 * @templatetag avatar
 */
class Avatar extends Wrapper {

	private $contributor;
	private $size;
	
	public function __construct($contributor, $size = 50) {
		$this->contributor = $contributor;
		$this->size = $size;
	}

	protected function getExtraFilterArgs() {
		return array($this->contributor, $this->size);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * Avatar image HTML
	 *
	 * Dimensions default to 50x50px.
	 * Change it via parameter: `avatar.html(32)`
	 * 
	 * @accessor
	 */
	public function html($size = null) {
		return $this->contributor->getAvatar(isset($size) && $size > 0 ? $size : $this->size);
	}

	/**
	 * Avatar image URL
	 *
	 * Dimensions default to 50x50px.
	 * Change it via parameter: `avatar.url(32)`
	 * 
	 * @accessor
	 */
	public function url($size = null) {
		return $this->contributor->getAvatarUrl(isset($size) && $size > 0 ? $size : $this->size);
	}

}