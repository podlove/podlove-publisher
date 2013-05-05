<?php 
namespace Podlove\Chapters\Printer;

class Nullprinter implements Printer {

	public function do_print( \Podlove\Chapters\Chapters $chapters ) {
		return '';
	}

}