<?php
namespace Podlove;

/**
 * Helper class to manage duration string.
 * @see http://podlove.org/simple-chapters/#Time
 */
class Duration {

	/**
	 * Raw user input
	 * @var string
	 */
	private $duration;

	private /* int */ $hours;
	private /* int */ $minutes;
	private /* int */ $seconds;
	private /* int */ $milliseconds;

	private /* bool */ $valid = true;

	public function __construct( $duration ) {
		$this->duration = trim( $duration );
		$this->normalize();
	}

	/**
	 * Extract time segments from duration string.
	 *
	 * - verifies validity
	 * - extracts hours, minutes, seconds, milliseconds
	 */
	private function normalize() {
		if ( $milliseconds = \Podlove\NormalPlayTime\Parser::parse( $this->duration, 'ms' ) ) {
			$this->hours        = floor((($milliseconds / 1000) / 60) / 60);
			$this->minutes      = floor(($milliseconds / 1000) / 60) % 60;
			$this->seconds      = floor($milliseconds / 1000) % 60;
			$this->milliseconds = $milliseconds % 1000;
		} else {
			$this->valid = false;
		}
	}

	/**
	 * Get duration in a certain format.
	 * 
	 * @param  string $format (optional) Time format.
	 *         Possibilities: full, HH:MM:SS, hours, minutes, seconds, milliseconds.
	 *         Default: full
	 * @return string
	 */
	public function get( $format = 'full' ) {

		if ( ! $this->valid ) {
			switch ( $format ) {
				case 'HH:MM:SS':
					return '00:00:00';
					break;
				case 'full': /* full is default */
				default:
					return '00:00:00.000';
					break;
			}			
		}

		switch ( $format ) {
			case 'hours':
				return $this->hours;
				break;
			case 'minutes':
				return $this->minutes;
				break;
			case 'seconds':
				return $this->seconds;
				break;
			case 'milliseconds':
				return $this->milliseconds;
				break;
			case 'HH:MM:SS':
				return $this->format( true, true, true, false );
				break;
			case 'human-readable':
				$duration_string = '';

				if ( $this->hours > 1 ) {
					$duration_string .= $this->hours . __(' hours ', 'podlove');
				} elseif ( $this->hours == 1 ) {
					$duration_string .= $this->hours . __(' hour ', 'podlove');
				}

				if ( $this->minutes >= 1 )
					$duration_string .= $this->minutes . __(' minutes ', 'podlove');

				if ( $this->hours == 0 && $this->minutes == 0 )
					$duration_string .= $this->seconds . __(' seconds', 'podlove');

				return $duration_string;
			break;
			case 'full': /* full is default */
			default:
				return $this->format();
				break;
		}
	}

	/**
	 * Get duration specifying the required time segments.
	 * 
	 * @param  boolean $hours       
	 * @param  boolean $minutes     
	 * @param  boolean $seconds     
	 * @param  boolean $milliseconds
	 * @return string
	 */
	public function format( $hours = true, $minutes = true, $seconds = true, $milliseconds = true ) {
		$duration = '';

		if ( $hours )
			$duration .= lfill( $this->hours, 2, '0' ) . ':';

		if ( $minutes )
			$duration .= lfill( $this->minutes, 2, '0' ) . ':';

		if ( $seconds )
			$duration .= lfill( $this->seconds, 2, '0' );

		if ( $milliseconds )
			$duration .= '.' . rfill( $this->milliseconds, 3, '0' );
			
		return $duration;
	}
}

/**
 * Append characters to the right of the given string until a length is reached.
 * 
 * @param  string $string  
 * @param  int    $length  
 * @param  string $fillchar
 * @return string
 */
function rfill( $string, $length, $fillchar = ' ' ) {
	while ( strlen( $string ) < $length ) {
		$string .= $fillchar;
	}
	return $string;
}

/**
 * Append characters to the left of the given string until a length is reached.
 * 
 * @param  string $string  
 * @param  int    $length  
 * @param  string $fillchar
 * @return string
 */
function lfill( $string, $length, $fillchar = ' ' ) {
	while ( strlen( $string ) < $length ) {
		$string = $fillchar . $string;
	}
	return $string;
}
