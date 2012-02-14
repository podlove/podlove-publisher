<?php
namespace Podlove;

/**
 * Conventions
 * 
 * 	Plugin Name:		This Is My Plugin
 * 	Plugin Namespace:	ThisIsMyPlugin
 * 	Plugin File:		this-is-my-plugin.php
 * 	Plugin Textdomain:	this-is-my-plugin
 * 	Plugin Directory:	this-is-my-plugin
 */
define( 'PLUGIN_FILE_NAME', strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', __NAMESPACE__ ) ) . '.php' );
$dir = dirname( __FILE__ );
define( 'PLUGIN_DIR' , substr( $dir, 0, strrpos( $dir, '/' ) + 1 ) );
define( 'PLUGIN_FILE', PLUGIN_DIR . PLUGIN_FILE_NAME );

/**
 * Get a value of the plugin header
 */
function get_plugin_header( $tag_name ) {
	static $plugin_data; // only load file once
	
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}
	
	$plugin_data  = get_plugin_data( PLUGIN_FILE );
	
	return $plugin_data[ $tag_name ];
}

define( 'PLUGIN_NAME', get_plugin_header( 'Name' ) );
define( 'TEXTDOMAIN', strtolower( str_replace( ' ', '-', PLUGIN_NAME ) ) );
load_plugin_textdomain( TEXTDOMAIN, FALSE, TEXTDOMAIN . '/languages' );
