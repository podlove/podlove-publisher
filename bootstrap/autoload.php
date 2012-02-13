<?php
// autoload all classes in /lib
function podlove_autoloader( $class_name ) {
	// get class name without namespace
	$split  = explode( '\\', $class_name );
	// remove <Plugin> namespace
	$plugin = array_shift( $split ); 
	
	// only load classes prefixed with <Plugin> namespace
	if ( $plugin != "Podlove" )
		return false;
	
	// class name without namespace
	$class_name = array_pop( $split );
	// CamelCase to snake_case
	$class_name = preg_replace( '/([a-z])([A-Z])/', '$1_$2', $class_name );

	// the rest of the namespace, if any
	$namespaces = $split;

	// library directory
	$lib = dirname( __FILE__ ) . '/../lib/';
	
	// register all possible paths for the class
	$possibilities = array();
	if ( count( $namespaces ) >= 1 ) {
		$possibilities[] = strtolower( $lib . implode( '/', $namespaces ) . '/' . $class_name . '.php' );
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