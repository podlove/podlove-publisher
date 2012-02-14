<?php
namespace Podlove;

function plove_init() {
	new Podlove;
}
add_action( 'plugins_loaded', '\Podlove\plove_init' );

class Podlove {
	
	function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		
		register_activation_hook( __FILE__,   array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		register_uninstall_hook( __FILE__,    array( __CLASS__, 'uninstall' ) );
	}
	
	/**
	 * Register custom post type "podcast".
	 */
	public function register_post_type() {
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
