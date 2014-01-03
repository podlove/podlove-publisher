<?php
namespace Podlove\Modules\Contributors\Template;

use Podlove\Template\Wrapper;

/**
 * Contributor Template Wrapper
 *
 * @templatetag contributor
 */
class Contributor extends Wrapper {

	private $contributor;
	private $contribution;

	public function __construct($contributor, $contribution = null) {
		$this->contributor = $contributor;
		$this->contribution = $contribution;
	}

	protected function getExtraFilterArgs() {
		return array($this->contributor, $this->contribution);
	}

	// /////////
	// Accessors
	// /////////

	public function name() {
		return $this->contributor->getName();
	}

	public function role() {
		if ($this->contribution) {
			return $this->contribution->getRole()->title;
		} else {
			return $this->contributor->getRole()->title;
		}
	}

	public function avatar($size = 50) {
		return $this->contributor->getAvatar($size);
	}

	public function website() {
		return $this->contributor->www;
	}

	public function episodes() {
		$episodes = array();

		foreach ($this->contributor->getContributions() as $contribution) {
			if ($episode = $contribution->getEpisode()) {
				$episodes[] = new Episode($episode);
			}
		}

		return $episodes;
	}

}