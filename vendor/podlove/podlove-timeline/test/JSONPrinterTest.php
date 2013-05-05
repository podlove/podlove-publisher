<?php
use \Podlove\Chapters\Parser;
use \Podlove\Chapters\Printer;
use \Podlove\Chapters\Chapters;
use \Podlove\Chapters\Chapter;

class JSONPrinterTest extends PHPUnit_Framework_TestCase {

	public function testPrinter() {
		$expected_print = json_encode(json_decode('[
	{ "start": "00:00:01.234", "title": "Intro", "href": "http://example.com", "image": "" },
	{ "start": "00:12:34.000", "title": "About us", "href": "", "image": "" },
	{ "start": "01:02:03.000", "title": "Later", "href": "", "image": "http://example.com/foo.jpg" }
]'));

		$chapters = new Chapters();
		$chapters->addChapter( new Chapter( 1234, 'Intro', 'http://example.com' ) );
		$chapters->addChapter( new Chapter( 754000, 'About us' ) );
		$chapters->addChapter( new Chapter( 3723000, 'Later', '', 'http://example.com/foo.jpg' ) );
		$chapters->setPrinter( new Printer\JSON() );

	    $this->assertEquals( $expected_print, (string) $chapters );
	}

}