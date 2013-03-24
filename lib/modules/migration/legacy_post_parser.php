<?php
namespace Podlove\Modules\Migration;

/**
 * Parser for WordPress posts from legacy Podcasting plugins like
 * PodPress and PowerPress.
 */
class Legacy_Post_Parser {

	public $post_id;
	private $duration;
	private $post;
	private $podPress_meta;

	public function __construct( $post_id ) {
		$this->post_id = $post_id;
		$this->post = get_post( $post_id );
		$this->parse();
	}

	private function parse() {

		foreach ( Enclosure::all_for_post( $this->post_id ) as $enclosure ) {
			if ( $enclosure->duration )
				$this->duration = $enclosure->duration;
		}

		$this->podPress_meta = get_post_meta( $this->post_id, '_podPressPostSpecific', true );
	}

	function get_duration() {
		return $this->duration;
	}

	function get_subtitle() {

		$subtitle = get_post_meta( $this->post_id, 'subtitle', true );

		if ( isset( $this->podPress_meta['itunes:subtitle'] ) && substr( $this->podPress_meta['itunes:subtitle'], 0, 2) !== "##" ) {
			$subtitle = $this->podPress_meta['itunes:subtitle'];
		}

		return $subtitle;
	}

	function get_summary() {

		$summary = get_post_meta( $this->post_id, 'summary', true );

		if ( isset( $this->podPress_meta['itunes:summary'] ) && substr( $this->podPress_meta['itunes:summary'], 0, 2) !== "##" ) {
			$summary = $this->podPress_meta['itunes:summary'];
		}

		return $summary;
	}

}