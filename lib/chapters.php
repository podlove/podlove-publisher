<?php
namespace Podlove;

/**
 * Chapter management class.
 * Consumes mp4chaps and spits out valid psc xml.
 *
 * @see http://podlove.org/simple-chapters/
 */
class Chapters {

	/**
	 * List of chapter maps. Keys: start, title, link, image
	 * @var array
	 */
	private $chapters = array();

	/**
	 * Parse mp4chaps string into list of php maps.
	 * 
	 * @param  string $chapters_string
	 */
	public static function from_mp4chaps( $chapters_string ) {

		$chapters = new self();
		$chapters_string = trim( $chapters_string );
		foreach( preg_split( "/((\r?\n)|(\r\n?))/", $chapters_string ) as $line ) {
		    $valid = preg_match( '/^((?:\d+\:[0-5]\d\:[0-5]\d(?:\.\d+)?)|\d+(?:\.\d+)?)(.*)$/', trim( $line ), $matches );

		    if ( ! $valid ) continue;

		    $start = trim( $matches[1] );
			$title = trim( $matches[2] );

			$link = '';
			$title = preg_replace_callback( '/\s?<[^>]+>\s?/' , function ( $matches ) use ( &$link ) {
				$link = trim( $matches[0], ' < >' );
				return ' ';
			}, $title );

			$chapters->add_chapter( array(
				'start' => $start,
				'title' => $title,
				'link'  => $link
			) );
		} 

		return $chapters;
	}

	function render_as_psc() {

		$xml = '<psc:chapters version="1.1" xmlns:psc="http://podlove.org/simple-chapters">';
		foreach ( $this->chapters as $chapter ) {
			$xml .= "\n\t<psc:chapter";
			$xml .= ' start="' . $chapter['start'] . '"';
			$xml .= ' title="' . htmlspecialchars( $chapter['title'] ) . '"';

			if ( isset( $chapter['link'] ) && $chapter['link'] )
				$xml .= ' link="' . $chapter['link'] . '"';

			if ( isset( $chapter['image'] ) && $chapter['image'] )
				$xml .= ' image="' . $chapter['image'] . '"';

			$xml .= " />";
		}
		$xml .= "\n" . '</psc:chapters>' . "\n";

		return $xml;
	}

	public function add_chapter( $chapter ) {
		$this->chapters[] = $chapter;
	}

	public function is_empty() {
		return count( $this->chapters ) === 0;
	}

}
