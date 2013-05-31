<?php 
namespace Podlove\Chapters\Parser;
use \Podlove\Chapters\Chapters;
use \Podlove\Chapters\Chapter;
use \Podlove\NormalPlayTime;

class PSC {

	public static function parse( $chapters_string ) {
		
		$chapters_string = trim( $chapters_string );
		$chapters = new Chapters();

		$xml = self::get_simplexml( $chapters_string );
		$xml->registerXPathNamespace( 'psc', 'http://podlove.org/simple-chapters' );
		$chapters_xpath = $xml->xpath("//psc:chapter");

		if ( ! $chapters_xpath )
			return NULL;

		foreach ( $chapters_xpath as $chapter ) {
			$chapters->addChapter(
				new Chapter(
					NormalPlayTime\Parser::parse( $chapter->attributes()->start ),
					$chapter->attributes()->title,
					$chapter->attributes()->href,
					$chapter->attributes()->image
				)
			);
		}

		return $chapters;
	}

	private static function get_simplexml( $string ) {

		libxml_use_internal_errors(true);
		$dom = new \DOMDocument("1.0", "UTF-8");
		$dom->strictErrorChecking = false;
		$dom->validateOnParse = false;
		$dom->recover = true;
		$dom->loadXML($string);
		$xml = simplexml_import_dom($dom);
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		return $xml;
	}

}