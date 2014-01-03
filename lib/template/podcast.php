<?php
namespace Podlove\Template;

/**
 * Podcast Template Wrapper
 *
 * @templatetag podcast
 */
class Podcast extends Wrapper {

	/**
	 * @var Podlove\Model\Podcast
	 */
	private $podcast;

	public function __construct(\Podlove\Model\Podcast $podcast) {
		$this->podcast = $podcast;
	}

	protected function getExtraFilterArgs() {
		return array($this->podcast);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * Podcast title
	 *
	 * @accessor
	 */
	public function title() {
		return $this->podcast->title;
	}

	/**
	 * Podcast subtitle
	 *
	 * @accessor
	 */
	public function subtitle() {
		return $this->podcast->subtitle;
	}

	/**
	 * Podcast summary
	 *
	 * @accessor
	 */
	public function summary() {
		return $this->podcast->summary;
	}

	/**
	 * Podcast image URL
	 *
	 * @accessor
	 */
	public function imageUrl() {
		return $this->podcast->cover_image;
	}

	/**
	 * Podcast author name
	 *
	 * @accessor
	 */
	public function authorName() {
		return $this->podcast->author_name;
	}

	/**
	 * Podcast owner name
	 *
	 * @accessor
	 */
	public function ownerName() {
		return $this->podcast->owner_name;
	}

	/**
	 * Podcast owner email
	 *
	 * @accessor
	 */
	public function ownerEmail() {
		return $this->podcast->owner_email;
	}

	/**
	 * Podcast publisher name
	 *
	 * @accessor
	 */
	public function publisherName() {
		return $this->podcast->publisher_name;
	}

	/**
	 * Podcast publisher url
	 *
	 * @accessor
	 */
	public function publisherUrl() {
		return $this->podcast->publisher_url;
	}

	/**
	 * Podcast license
	 *
	 * @see  license
	 * @accessor
	 */
	public function license() {
		return new License(
			new \Podlove\Model\License(
				"podcast",
				array(
					'type'                 => $this->podcast->license_type,
					'license_name'         => $this->podcast->license_name,
					'license_url'          => $this->podcast->license_url,
					'allow_modifications'  => $this->podcast->license_cc_allow_modifications,
					'allow_commercial_use' => $this->podcast->license_cc_allow_commercial_use,
					'jurisdiction'         => $this->podcast->license_cc_license_jurisdiction,
				)
			)
		);
	}

}