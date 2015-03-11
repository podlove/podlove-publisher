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
		$this->list = $list;

		$podcasts = $list->get_podcasts();

		$returned_podcasts = array();
		foreach ( $podcasts as $podcast ) {
			$returned_podcasts[] = new \Podlove\Template\Podcast(
				\Podlove\Model\Podcast::get($podcast->blog_id)
			);
		}

		$this->list->podcasts = $returned_podcasts;
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
	public function episodes( $args = array() ) {
		$number_of_episodes = isset( $args['limit'] ) && is_numeric( $args['limit'] ) ? $args['limit'] : 10;
		$orderby = isset( $args['orderby'] ) && $args['orderby'] ? $args['orderby'] : 'post_date';
		$order   = isset( $args['order'] )   && $args['order']   ? $args['order']   : 'DESC';

		return $this->list->latest_episodes( $number_of_episodes, $orderby, $order );
	}
}