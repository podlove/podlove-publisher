<?php
namespace Podlove\Template;

class Podcast {

	/**
	 * @var Podlove\Model\Podcast
	 */
	private $podcast;

	public function __construct(\Podlove\Model\Podcast $podcast) {
		$this->podcast = $podcast;
	}

	// /////////
	// Accessors
	// /////////

	public function title() {
		return $this->podcast->title;
	}

	public function subtitle() {
		return $this->podcast->subtitle;
	}

	public function summary() {
		return $this->podcast->summary;
	}

	public function imageUrl() {
		return $this->podcast->cover_image;
	}

	public function authorName() {
		return $this->podcast->author_name;
	}

	public function ownerName() {
		return $this->podcast->owner_name;
	}

	public function ownerEmail() {
		return $this->podcast->owner_email;
	}

	public function publisherName() {
		return $this->podcast->publisher_name;
	}

	public function publisherUrl() {
		return $this->podcast->publisher_url;
	}

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