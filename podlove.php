<?php
/*
Plugin Name: Podlove Podcasting Plugin for WordPress
Plugin URI: 
Description: The one and only podcast client. Seriously.
Version: 1.0
Author: Eric Teubert
Author URI: ericteubert@googlemail.com
License: MIT
*/

function plove_init() {
	new Podlove;
}
add_action( 'plugins_loaded', 'plove_init' );

function podlove_map_slugs( $term ) {
	return $term->slug;
}

class Podlove {
	
	function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		
		register_activation_hook( __FILE__,   array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		register_uninstall_hook( __FILE__,    array( __CLASS__, 'uninstall' ) );
	}
	
	/**
	 * Shorthand translation function.
	 * 
	 * @param string $text
	 * @return string
	 */
	public static function t( $text ) {
		return __( $text, 'podlove' );
	}
	
	/**
	 * Register custom post type "podcast".
	 */
	public function register_post_type() {
		require_once 'podcast-post-type.php';
		new Podcast_Post_Type();
	}
	
	/**
	 * Callback function when activating the plugin.
	 */
	public function activate() {
		// ...
	}
	
	/**
	 * Callback function when deactivating the plugin.
	 */
	public function deactivate() {
		// ...
	}
	
	/**
	 * Callback function when uninstalling the plugin.
	 */
	static function uninstall() {
		// remove every trace of the existence of this plugin
	}
	
}
