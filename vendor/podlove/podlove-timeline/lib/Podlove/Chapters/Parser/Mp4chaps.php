<?php 
namespace Podlove\Chapters\Parser;
use \Podlove\Chapters\Chapters;
use \Podlove\Chapters\Chapter;
use \Podlove\NormalPlayTime;

class Mp4chaps {

	public static function parse( $chapters_string ) {

		$chapters_string = trim( $chapters_string );

		if ( ! strlen( $chapters_string ) )
			return NULL;

		$chapters = new Chapters();
		$invalid_lines = 0;

		foreach( preg_split( "/((\r?\n)|(\r\n?))/", $chapters_string ) as $line ) {
		    $valid = preg_match( '/^([\d.:]+)(.*)$/', trim( $line ), $matches );

		    if ( ! $valid ) {
		    	$invalid_lines++;
		    	continue;
		    }

		    $time_string = $matches[1];
			$title       = $matches[2];
		    $timestamp_milliseconds = NormalPlayTime\Parser::parse( $time_string );

		    if ( $timestamp_milliseconds === false ) continue;

			$link = '';
			$title = preg_replace_callback( '/\s?<[^>]+>\s?/' , function ( $matches ) use ( &$link ) {
				$link = trim( $matches[0], ' < >' );
				return ' ';
			}, $title );

			$chapters->addChapter( new Chapter( $timestamp_milliseconds, trim( $title ), $link ) );
		}

		return $invalid_lines <= count( $chapters ) ? $chapters : NULL;
	}

}