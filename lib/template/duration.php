<?php
namespace Podlove\Template;

/**
 * Duration Template Wrapper
 *
 * @templatetag duration
 */
class Duration extends Wrapper {

	private $episode;
	
	public function __construct(\Podlove\Model\Episode $episode) {
		$this->episode = $episode;
	}

	protected function getExtraFilterArgs() {
		return array($this->episode);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * @deprecated since 1.10.3
	 */
	public function __toString() {
		return $this->hours()
		     . ":" . self::lfill( $this->minutes(), 2, 0 )
		     . ":" . self::lfill( $this->seconds(), 2, 0 );
	}

	/**
	 * Append characters to the left of the given string until a length is reached.
	 * 
	 * @param  string $string  
	 * @param  int    $length  
	 * @param  string $fillchar
	 * @return string
	 */
	private static function lfill( $string, $length, $fillchar = ' ' ) {
		while ( strlen( $string ) < $length ) {
			$string = $fillchar . $string;
		}
		return $string;
	}

	/**
	 * Hours
	 *
	 * 0,1,2,…
	 * 
	 * @accessor
	 */
	public function hours() {
		return $this->episode->get_duration('hours');
	}

	/**
	 * Minutes
	 *
	 * 0,1,2,…,59
	 * 
	 * @accessor
	 */
	public function minutes() {
		return $this->episode->get_duration('minutes');
	}

	/**
	 * Seconds
	 *
	 * 0,1,2,…,59
	 * 
	 * @accessor
	 */
	public function seconds() {
		return $this->episode->get_duration('seconds');
	}

	/**
	 * Milliseconds
	 *
	 * 0,1,2,…,999
	 * 
	 * @accessor
	 */
	public function milliseconds() {
		return $this->episode->get_duration('milliseconds');
	}

	/**
	 * The total duration in milliseconds
	 *
	 * 0,1,2,…
	 * 
	 * @accessor
	 */
	public function totalMilliseconds() {
		return \Podlove\NormalPlayTime\Parser::parse( $this->duration, 'ms' );
	}

}