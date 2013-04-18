<?php
namespace Podlove;

/**
 * Parse partial seconds to milliseconds.
 *
 * Expects the integer from behind the dot.
 * If the original time string is "1.23" it expects "23" and will return "230"
 * 
 * @param  string $ms_string
 * @return int
 */
function parse_ms_string( $ms_string ) {

	switch ( strlen( trim( $ms_string ) ) ) {
		case 0: return 0;
		case 1: return $ms_string * 100;
		case 2: return $ms_string * 10;
		default: return (int) substr( $ms_string, 0, 3 );
	}
}

/**
 * Parse Normal Playtime.
 *
 * Source: http://www.w3.org/TR/media-frags/#npttimedef
 * 
 * npt-sec       =  1*DIGIT [ "." *DIGIT ]                     ; definitions taken
 * npt-hhmmss    =  npt-hh ":" npt-mm ":" npt-ss [ "." *DIGIT] ; from RFC 2326,
 * npt-mmss      =  npt-mm ":" npt-ss [ "." *DIGIT] 
 * npt-hh        =   1*DIGIT     ; any positive number
 * npt-mm        =   2DIGIT      ; 0-59
 * npt-ss        =   2DIGIT      ; 0-59
 * npttime       = npt-sec / npt-mmss / npt-hhmmss
 * 
 * @param  string $time_string
 * @param  string $output      'ms' for milliseconds, 's' for seconds. default: 'ms'
 * @return int
 */
function parse_npt( $time_string, $output = 'ms' ) {

	$ms = NULL;
	$time_string = trim( $time_string );

	$npt_sec    =                 "/^(\d+)(?:\.(\d+))?$/";
	$npt_mmss   =       "/^(\d\d?):(\d\d?)(?:\.(\d+))?$/";
	$npt_hhmmss = "/^(\d+):(\d\d?):(\d\d?)(?:\.(\d+))?$/";

	if ( preg_match( $npt_sec, $time_string, $matches ) ) {
		$ms = $matches[1] * 1000;
		if ( isset( $matches[2] ) ) $ms += parse_ms_string($matches[2]);
	} elseif ( preg_match( $npt_mmss, $time_string, $matches ) ) {
		if ( $matches[2] >= 60 ) return NULL;
		$ms = ($matches[1] * 60 + $matches[2]) * 1000;
		if ( isset( $matches[3] ) ) $ms += parse_ms_string($matches[3]);
	} elseif ( preg_match( $npt_hhmmss, $time_string, $matches ) ) {
		if ( $matches[2] >= 60 || $matches[3] >= 60 ) return NULL;
		$ms = ( ( $matches[1] * 60 + $matches[2] ) * 60 + $matches[3]) * 1000;
		if ( isset( $matches[4] ) ) $ms += parse_ms_string($matches[4]);
	}

	return $output == 'ms' ? $ms : floor( $ms / 1000 );
}

// function assert_equal( $expected, $actual ) {
// 	if ( $expected == $actual ) {
// 		echo ".";
// 	} else {
// 		echo "F\n $expected != $actual\n";
// 	}
// }

// assert_equal( parse_ms_string(" 1"), 100 );
// assert_equal( parse_ms_string("12"), 120 );
// assert_equal( parse_ms_string("123"), 123 );
// assert_equal( parse_ms_string("1234"), 123 );

// // output: seconds
// assert_equal( parse_npt(" 1.234", 's'), 1 );
// assert_equal( parse_npt(" 1.834", 's'), 1 );

// // sec
// assert_equal( parse_npt("1"), 1000 );
// assert_equal( parse_npt("1.234"), 1234 );
// assert_equal( parse_npt("1.2345"), 1234 );
// assert_equal( parse_npt("12345.6"), 12345600 );

// // mmss
// assert_equal( parse_npt("12:34"), 754000 );
// assert_equal( parse_npt("12:34.5"), 754500 );
// assert_equal( parse_npt("12:34.56"), 754560 );
// assert_equal( parse_npt("1:23"), 83000 );
// assert_equal( parse_npt("1:2"), 62000 );
// assert_equal( parse_npt("1:02"), 62000 );

// // hhmmss
// assert_equal( parse_npt("1:2:3"), 3723000 );
// assert_equal( parse_npt("1:2:3.4"), 3723400 );
// assert_equal( parse_npt("123:4:5"), 443045000 );

// // invalid
// assert_equal( parse_npt("abc"), NULL );
// assert_equal( parse_npt("1:60"), NULL );
// assert_equal( parse_npt("1:160"), NULL );
// assert_equal( parse_npt("1:61:5"), NULL );
// assert_equal( parse_npt("1:62:63"), NULL );
// assert_equal( parse_npt("100:02"), NULL );

// echo "\n";