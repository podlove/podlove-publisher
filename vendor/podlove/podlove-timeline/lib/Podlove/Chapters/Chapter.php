<?php 
namespace Podlove\Chapters;

class Chapter {

	private $timestamp;
	private $title;
	private $link;
	private $image;

	public function __construct( $timestamp, $title, $link = '', $image = '' ) {
		$this->timestamp = $timestamp;
		$this->title = $title;
		$this->link = $link;
		$this->image = $image;
	}

	public function get_title() {
		return $this->title;
	}

	public function get_link() {
		return $this->link;
	}

	public function get_image() {
		return $this->image;
	}

	public function get_time() {
		$ms = $this->timestamp % 1000;
		$s  = ($this->timestamp / 1000) % 60;
		$m  = ($this->timestamp / 1000 / 60 ) % 60;
		$h  = floor($this->timestamp / 1000 / 60 / 60);

		while ( strlen( $h )  < 2 ) $h  = "0$h";
		while ( strlen( $m )  < 2 ) $m  = "0$m";
		while ( strlen( $s )  < 2 ) $s  = "0$s";
		while ( strlen( $ms ) < 3 ) $ms = "0$ms";

		return "$h:$m:$s.$ms";
	}

}