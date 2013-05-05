<?php 
namespace Podlove\Chapters\Parser;
use \Podlove\Chapters\Chapters;
use \Podlove\Chapters\Chapter;
use \Podlove\NormalPlayTime;

class PSC {

	public static function parse( $chapters_string ) {
		
		$chapters = new Chapters();

		$xml = new \SimpleXMLElement( trim( $chapters_string ) );
		$xml->registerXPathNamespace( 'psc', 'http://podlove.org/simple-chapters' );

		foreach ( $xml->xpath("//psc:chapter") as $chapter ) {
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

}