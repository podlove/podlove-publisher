<?php 
namespace Podlove\Modules\Seasons\Template;

use Podlove\Template\Wrapper;

/**
 * Season Template Wrapper
 *
 * @templatetag season
 */
class Season extends Wrapper {

	private $season;

	public function __construct(\Podlove\Modules\Seasons\Model\Season $season) {
		$this->season = $season;
	}

	protected function getExtraFilterArgs() {
		return array($this->season);
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
		return $this->season->title();
	}

	/**
	 * Subtitle
	 *
	 * @accessor
	 */
	public function subtitle() {
		return $this->season->subtitle;
	}

	/**
	 * Summary
	 *
	 * @accessor
	 */
	public function summary() {
		return $this->season->summary;
	}

	/**
	 * Automatically assigned season number, starting at 1.
	 * 
	 * @accessor
	 */
	public function number() {
		return $this->season->number();
	}

	/**
	 * Image
	 *
	 * @accessor
	 */
	public function image() {
		return new \Podlove\Template\Image($this->season->image());
	}

	/**
	 * Start Date
	 * 
	 * This is the configured start date, not the date of the first episode of the season.
	 * If you were looking for that, use `season.firstEpisode.publicationDate`.
	 *
	 * @see  datetime
	 * @accessor
	 */
	public function startDate() {
		return new \Podlove\Template\DateTime(strtotime($this->season->start_date));
	}

	/**
	 * First episode of the season.
	 * 
	 * @see  episode
	 * @accessor
	 */
	public function firstEpisode() {
		return new \Podlove\Template\Episode($this->season->first_episode());
	}

	/**
	 * Last episode of the season.
	 * 
	 * @see  episode
	 * @accessor
	 */
	public function lastEpisode() {
		return new \Podlove\Template\Episode($this->season->last_episode());
	}

	/**
	 * Is this season currently running?
	 * 
	 * ```jinja
	 * {% if season.running %}
	 *     This season is currently running.
	 * {% endif %}
	 * ```
	 * 
	 * @accessor
	 */
	public function running() {
		return $this->season->is_running();
	}

	/**
	 * Season Episodes
	 * 
	 * @accessor
	 */
	public function episodes() {
		return array_map(function($episode) {
			return new \Podlove\Template\Episode($episode);
		}, $this->season->episodes());
	}
}
