<?php
namespace Podlove\Modules\Social\Template;

use Podlove\Template\Wrapper;

/**
 * Service Template Wrapper
 *
 * Requires the "Social" module.
 *
 * @templatetag service
 */
class Service extends Wrapper {

	/**
	 * @var \Podlove\Modules\Social\Model\ContributorService
	 */
	private $contributor_service;

	/**
	 * @var \Podlove\Modules\Social\Model\Service
	 */
	private $service;

	public function __construct($contributor_service, $service = null) {
		$this->contributor_service = $contributor_service;
		$this->service = $service;
	}

	protected function getExtraFilterArgs() {
		return array($this->contributor_service, $this->service);
	}


	// /////////
	// Accessors
	// /////////

	/**
	 * Service title
	 * 
	 * @accessor
	 */
	public function title() {
		if ($this->contributor_service && $this->contributor_service->title) {
			return $this->contributor_service->title;
		} else {
			return $this->service->title;
		}
	}

	/**
	 * Service description
	 * 
	 * @accessor
	 */
	public function description() {
		return $this->service->description;
	}

	/**
	 * Service profile URL
	 * 
	 * @accessor
	 */
	public function profileUrl() {
		return $this->contributor_service->get_service_url();
	}

	/**
	 * Service value
	 *
	 * Normally, you want to access the generates url via `profileUrl()`.
	 * But in case you need the raw user value, use this method.
	 * 
	 * @accessor
	 */
	public function rawValue() {
		return $this->contributor_service->value;
	}

	/**
	 * Logo URL
	 * 
	 * @accessor
	 */
	public function logoUrl() {
		return $this->service->get_logo();
	}
}