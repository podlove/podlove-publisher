<?php
namespace Podlove\Template;

/**
 * DateTime Template Wrapper
 * 
 * @templatetag datetime
 */
class DateTime extends Wrapper {

	private $timestamp;

	public function __construct($timestamp) {
		$this->timestamp = (int) $timestamp;
	}

	protected function getExtraFilterArgs() {
		return array($this->timestamp);
	}

	// /////////
	// Accessors
	// /////////
	
	/**
	 * $format parameter is @deprecated, use DateTime.format instead
	 */
	public function __toString() {
		$format = get_option('date_format') . ' ' . get_option('time_format');
		return date_i18n($format, $this->timestamp);
	}

	/**
	 * Year
	 * 
	 * @accessor
	 */
	public function year() {
		return date('Y', $this->timestamp);
	}

	/**
	 * Month number
	 * 
	 * @accessor
	 */
	public function month() {
		return date('m', $this->timestamp);
	}

	/**
	 * Day of the month
	 * 
	 * @accessor
	 */
	public function day() {
		return date('d', $this->timestamp);
	}

	/**
	 * Hours of the day, 24h format
	 * 
	 * @accessor
	 */
	public function hours() {
		return date('H', $this->timestamp);
	}

	/**
	 * Minutes of the current hour
	 * 
	 * @accessor
	 */
	public function minutes() {
		return date('i', $this->timestamp);
	}

	/**
	 * Seconds of the current minute
	 * 
	 * @accessor
	 */
	public function seconds() {
		return date('s', $this->timestamp);
	}

	/**
	 * Custom time format
	 *
	 * See [PHP date documentation](http://php.net/manual/en/function.date.php) for available formats
	 * 
	 * @accessor
	 */
	public function format($format) {
		return date_i18n($format, $this->timestamp);
	}
}
