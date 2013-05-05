<?php 
namespace Podlove\Chapters;

class Chapters implements \Iterator, \ArrayAccess {

	private $chapters = array();
	private $printer = NULL;

	public function __construct() {
		$this->setPrinter( new Printer\Nullprinter() );
	}

	public function addChapter( $chapter ) {
		$this->chapters[] = $chapter;
	}

	public function __toString() {
		return $this->printer->do_print( $this );
	}

	public function setPrinter( \Podlove\Chapters\Printer\Printer $printer ) {
		$this->printer = $printer;
	}

	public function toArray() {
		return $this->chapters;
	}

	/**
	 * Iterator Methods
	 */

	function rewind() {
		return reset( $this->chapters );
	}

	function current() {
		return current( $this->chapters );
	}

	function key() {
		return key( $this->chapters );
	}

	function next() {
		return next( $this->chapters );
	}

	function valid() {
		return key( $this->chapters ) !== null;
	}

	/**
	 * ArrayAccess Methods
	 */
	
	public function offsetSet($offset, $value) {
	    if (is_null($offset)) {
	        $this->chapters[] = $value;
	    } else {
	        $this->chapters[$offset] = $value;
	    }
	}

	public function offsetExists($offset) {
	    return isset($this->chapters[$offset]);
	}

	public function offsetUnset($offset) {
	    unset($this->chapters[$offset]);
	}

	public function offsetGet($offset) {
	    return isset($this->chapters[$offset]) ? $this->chapters[$offset] : null;
	}

}