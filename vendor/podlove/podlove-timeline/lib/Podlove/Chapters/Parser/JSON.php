<?php 
namespace Podlove\Chapters\Parser;
use \Podlove\Chapters\Chapters;
use \Podlove\Chapters\Chapter;
use \Podlove\NormalPlayTime;

class JSON {

	public static function parse( $chapters_string ) {
		
		$chapters = new Chapters();

		$json = json_decode( trim( $chapters_string ) );

		if ( ! $json )
			return $chapters;

		foreach ( $json as $chapter ) {
			$chapters->addChapter(
				new Chapter(
					NormalPlayTime\Parser::parse( $chapter->start ),
					$chapter->title,
					$chapter->href,
					$chapter->image
				)
			);
		}

		return $chapters;
	}

}