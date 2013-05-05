<?php 
namespace Podlove\Chapters\Printer;

class Mp4chaps implements Printer {

	public function do_print( \Podlove\Chapters\Chapters $chapters ) {
		return implode( "\n", array_map( function ( $chapter ) {
			return $chapter->get_time()
			     . ' '
			     . $chapter->get_title()
			     . (
			     	$chapter->get_link() ? ' <' . $chapter->get_link() . '>' : ''
			       );
		}, $chapters->toArray() ) );

	}

}