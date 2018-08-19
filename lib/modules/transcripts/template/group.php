<?php 
namespace Podlove\Modules\Transcripts\Template;

use Podlove\Template\Wrapper;

/**
 * Transcript Group Template Wrapper
 *
 * @templatetag show
 */
class Group extends Wrapper {

	private $lines;
	private $contributor_identifier;

	public function __construct($lines, $contributor_identifier)
	{
		$this->lines = $lines;
		$this->contributor_identifier = $contributor_identifier;
	}

	protected function getExtraFilterArgs() {
		return array($this->lines);
	}

	/**
	 * Items / Lines
	 *
	 * @accessor
	 */
	public function items()
	{
		return $this->lines;
	}

	/**
	 * Start time in ms
	 *
	 * @accessor
	 */
	public function start()
	{
		$first_line = reset($this->lines);
		return $first_line->start();
	}

	/**
	 * End time in ms
	 *
	 * @accessor
	 */
	public function end()
	{
		$last_line = end($this->lines);
		return $last_line->end();
	}

	/**
	 * Voice / Contributor
	 *
	 * @accessor
	 */
	public function contributor()
	{
		if (!$this->contributor_identifier) {
			return null;
		}

		$contributor = \Podlove\Modules\Contributors\Model\Contributor::find_one_by_property("identifier", $this->contributor_identifier);

		if (!$contributor) {
			return null;
		}

		return new \Podlove\Modules\Contributors\Template\Contributor(
			$contributor
		);
	}
}
