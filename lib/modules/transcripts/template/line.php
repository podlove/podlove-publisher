<?php 
namespace Podlove\Modules\Transcripts\Template;

use Podlove\Template\Wrapper;

/**
 * Transcript Line Template Wrapper
 *
 * @templatetag show
 */
class Line extends Wrapper {

	private $line;

	public function __construct($line)
	{
		$this->line = $line;
	}

	protected function getExtraFilterArgs() {
		return array($this->line);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * Content
	 *
	 * @accessor
	 */
	public function content() {
		return $this->line->content;
	}

	/**
	 * Start time in ms
	 *
	 * @accessor
	 */
	public function start()
	{
		// fixme: this is silly, Duration should take ms as parameter, not a whole episode object
		$episode = new \Podlove\Model\Episode;
		$episode->duration = $this->line->start / 1000;

		return new \Podlove\Template\Duration($episode);
	}

	/**
	 * End time in ms
	 *
	 * @accessor
	 */
	public function end()
	{
		$episode = new \Podlove\Model\Episode;
		$episode->duration = $this->line->end / 1000;

		return new \Podlove\Template\Duration($episode);
	}

	/**
	 * Raw "voice" identifier from transcript
	 *
	 * @accessor
	 */
	public function voice()
	{
		return $this->line->voice;
	}

	/**
	 * Voice / Contributor
	 *
	 * @accessor
	 */
	public function contributor()
	{
		if (!$this->line->contributor_id) {
			return null;
		}

		$contributor = \Podlove\Modules\Contributors\Model\Contributor::find_by_id($this->line->contributor_id);

		if (!$contributor) {
			return null;
		}

		return new \Podlove\Modules\Contributors\Template\Contributor(
			$contributor
		);
	}
}
