<?php

function podlove_camelcase_to_snakecase( $string ) {
	return preg_replace( '/([a-z])([A-Z])/', '$1_$2', $string );
}

function podlove_camelsnakecase_to_camelcase( $string ) {
	return str_replace( '_', '', $string );
}

function podlove_snakecase_to_camelsnakecase( $string ) {
	// FIXME: /e is evil. use preg_replace_callback instead
	return ucwords( preg_replace( '/_\w/e', 'strtoupper("$0")', $string ) );
}

// autoload all classes in /lib
function podlove_autoloader( $class_name ) {
	// get class name without namespace
	$split  = explode( '\\', $class_name );
	// remove <Plugin> namespace
	$plugin = array_shift( $split );

	if ( ! strlen( $plugin ) )
		$plugin = array_shift( $split );
	
	// only load classes prefixed with <Plugin> namespace
	if ( $plugin != "Podlove" )
		return false;
	
	// class name without namespace
	$class_name = array_pop( $split );
	// CamelCase to snake_case
	$class_name = podlove_camelcase_to_snakecase( $class_name );

	// the rest of the namespace, if any
	$namespaces = $split;

	// library directory
	$lib = dirname( dirname( __FILE__ ) ) . '/lib/';
	
	// register all possible paths for the class
	$possibilities = array();
	if ( count( $namespaces ) >= 1 ) {
		$possibilities[] = strtolower( $lib . implode( '/', array_map( 'podlove_camelcase_to_snakecase', $namespaces ) ) . '/' . $class_name . '.php' );
	} else {
		$possibilities[] = strtolower( $lib . $class_name . '.php' );
	}
	
	// search for the class
	foreach ( $possibilities as $file ) {
		if ( file_exists( $file ) ) {
			require_once( $file );
			return true;
		}
	}
	
	return false;
}
spl_autoload_register( 'podlove_autoloader' );