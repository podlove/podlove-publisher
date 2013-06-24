<?php 
use Podlove\NormalPlayTime\Parser;

class NptParserTest extends PHPUnit_Framework_TestCase {

	public function testParserToMilliseconds() {
		 
		$examples = array(
			// sec
			"1" => 1000,
			"1.234" => 1234,
			"1.2345" => 1234,
			"12345.6" => 12345600,
			 
			// mmss
			"12:34" => 754000,
			"12:34.5" => 754500,
			"12:34.56" => 754560,
			"1:23" => 83000,
			"1:2" => 62000,
			"1:02" => 62000,
			 
			// hhmmss
			"1:2:3" => 3723000,
			"1:2:3.4" => 3723400,
			"123:4:5" => 443045000,

			// no hours, minutes > 59. not exactly NPT but common
			"62:3" => 3723000,
			"102:3" => 6123000,
			"102:3.123" => 6123123,
			 
			// invalid
			"abc" => NULL,
			"1:60" => NULL,
			"1:160" => NULL,
			"1:61:5" => NULL,
			"1:62:63" => NULL,
		);

		foreach ( $examples as $npt_string => $expected_ms ) {
			$this->assertEquals($expected_ms, Parser::parse($npt_string));
		}
	}

	public function testParserToSeconds( $value='' ) {
		$parser = new Parser();
		$this->assertEquals(1, Parser::parse(" 1.234", 's'));
		$this->assertEquals(1, Parser::parse(" 1.834", 's'));
	}

	public function testMsStringParser() {

		$examples = array(
			" 1" => 100,
			"12" => 120,
			"123" => 123,
			"1234" => 123
		);

		foreach ( $examples as $ms_string => $expected_ms ) {
			$this->assertEquals($expected_ms, Parser::parse_ms_string($ms_string));
		}
	}

}