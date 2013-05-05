<?php 
namespace Podlove\Chapters\Printer;

class JSON implements Printer {

	public function do_print( \Podlove\Chapters\Chapters $chapters ) {
		return json_encode( array_map( function ( $chapter ) {
			return (object) array(
				'start' => $chapter->get_time(),
				'title' => $chapter->get_title(),
				'href'  => $chapter->get_link(),
				'image' => $chapter->get_image(),
			);
		}, $chapters->toArray() ) );
	}
}