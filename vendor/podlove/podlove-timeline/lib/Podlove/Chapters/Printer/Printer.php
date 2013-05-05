<?php 
namespace Podlove\Chapters\Printer;

interface Printer {
	public function do_print( \Podlove\Chapters\Chapters $chapters );
}