<?php
namespace Podlove\Modules\Networks\Template;

use Podlove\Template\Wrapper;

/**
 * List Template Wrapper
 *
 * Requires the "Networks" module.
 *
 * @templatetag list
 */
class PodcastList extends Wrapper {

	/**
	 * @var \Podlove\Modules\Networks\Model\Network
	 */
	private $list;

	public function __construct( $list ) {
		$current_blog = get_current_blog_id();
		$this->list = $list;

		$podcasts = $list->get_podcasts();

		$returned_podcasts = array();
		foreach ( $podcasts as $podcast ) {
			switch_to_blog( $podcast->blog_id );
			$podcast_instance = new \Podlove\Template\Podcast(\Podlove\Model\Podcast::get_instance());
			$podcast_instance->blog_id = $podcast->blog_id;
			
			$returned_podcasts[] = $podcast_instance;
		}

		$this->list->podcasts = $returned_podcasts;
		switch_to_blog( $current_blog );
	}

	protected function getExtraFilterArgs() {
		return array();
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * List title
	 * 
	 * @accessor
	 */
	public function title() {
		return $this->list->title;
	}

	/**
	 * List subtitle
	 * 
	 * @accessor
	 */
	public function subtitle() {
		return $this->list->subtitle;
	}

	/**
	 * List description
	 * 
	 * @accessor
	 */
	public function description() {
		return $this->list->description;
	}

	/**
	 * List logo
	 * 
	 * @accessor
	 */
	public function logo() {
		return $this->list->logo;
	}

	/**
	 * List url
	 * 
	 * @accessor
	 */
	public function url() {
		return $this->list->url;
	}

	/**
	 * List podcasts
	 * 
	 * @accessor
	 */
	public function podcasts() {
		return $this->list->podcasts;
	}

	/**
	 * List latest episodes from network
	 * 
	 * @accessor
	 */
	public function episodes() {
		return $this->list->latest_episodes();
	}
}