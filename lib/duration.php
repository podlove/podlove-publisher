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
		if ( preg_match( '/^(:?\d+:)?(\d+:)(\d+)\.?(:?\d+)?$/', $this->duration, $matches ) ) {
			$this->hours        = isset( $matches[1] ) ? (int) $matches[1] : 0;
			$this->minutes      = isset( $matches[2] ) ? (int) $matches[2] : 0;
			$this->seconds      = isset( $matches[3] ) ? (int) $matches[3] : 0;
			$this->milliseconds = isset( $matches[4] ) ? (int) $matches[4] : 0;

			if ( $this->minutes > 59 || $this->seconds > 59 || $this->milliseconds > 999 ) {
				$this->valid = false;
			}
		} else {
			$this->valid = false;
		}
	}

	/**
	 * Get duration in a certain format.
	 * 
	 * @param  string $format (optional) Time format. Possibilities: full, second-accuracy. Default: full.
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
			case 'HH:MM:SS':
				return $this->format( true, true, true, false );
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

	public function is_valid() {
		return (bool) $valid;
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

/*
// Testcases
$durations = array(
	'08:22.12:'    => '00:00:00.000', // invalid format
	'08:222.12'    => '00:00:00.000', // invalid seconds
	'98:22.12'     => '00:00:00.000', // invalid minutes
	'10:22.1234'   => '00:00:00.000', // invalid milliseconds
	'00:08:22.117' => '00:08:22.117', // full qualified
	'08:22'        => '00:08:22.000', // MM:SS
	'08:22.12'     => '00:08:22.120', // MM:SS.mm (missing 0)
	'8:22.12'      => '00:08:22.120', // MM:SS.mm (missing 0)
	'8:2.12'       => '00:08:02.120', // MM:SS.mm (missing 0)
	'123:18:12.12' => '123:18:12.120', // HH:MM:SS.mm (long hours)
);

foreach ( $durations as $test_case => $expected ) {
	$d = new Duration( $test_case );
	$duration = $d->get();
	if ( $duration == $expected ) {
		echo ".";
	} else {
		echo "\n$duration != $expected\n";
	}
}
// check formatting
$d = new Duration( '00:08:22.117' );
if ( $d->get('HH:MM:SS') == '00:08:22' ) {
	echo '.';
} else {
	echo "ERROR";
}
echo "\n";
*/