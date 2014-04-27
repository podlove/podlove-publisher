<?php
namespace Podlove\Template;

/**
 * Network Template Wrapper
 *
 * @templatetag network
 */
class Network extends Wrapper {

	/**
	 * @var Podlove\Modules\Networks\Model\Network
	 */
	private $network;

	public function __construct(\Podlove\Modules\Networks\Model\Network $network) {
		$this->network = $network;
	}

	protected function getExtraFilterArgs() {
		return array($this->network);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * Title
	 *
	 * @accessor
	 */
	public function title() {
		return $this->network->title;
	}

	/**
	 * URL
	 *
	 * @accessor
	 */
	public function url() {
		return $this->network->url;
	}

	/**
	 * Subtitle
	 *
	 * @accessor
	 */
	public function subtitle() {
		return $this->network->subtitle;
	}

	/**
	 * Summary
	 *
	 * @accessor
	 */
	public function description() {
		return $this->network->description;
	}

	/**
	 * Image URL
	 *
	 * @accessor
	 */
	public function imageUrl() {
		return $this->network->logo;
	}

	/**
	 * Podcasts
	 *
	 * @accessor
	 */
	public function podcasts($args = array()) {
		$current_blog_id = get_current_blog_id();
		$podcasts = \Podlove\Modules\Networks\Model\Network::all_podcasts();
		return array_map(function ($podcast) {
			switch_to_blog( $podcast );
			return new Podcast(\Podlove\Model\Podcast::get_instance());
		}, $podcasts);	
		switch_to_blog( $current_blog_id );
	}

}