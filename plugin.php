<?php
namespace Podlove;

function plove_init() {
	new Podlove;
}
add_action( 'plugins_loaded', '\Podlove\plove_init' );

/**
 * Get all show formats or all formats for a given show.
 * 
 * @param int $show_id
 * @return array
 */
function get_show_formats( $show_id = NULL ) {
	$show_formats = get_option( '_podlove_show_formats' );
	
	if ( ! isset( $show_formats ) || ! is_array( $show_formats ) )
		$show_formats = array();
		
	if ( $show_id ) {
		if ( isset( $show_formats[ $show_id ] ) )
			return $show_formats[ $show_id ];
		else
			return array();
	} else {
		return $show_formats;
	}
}

/**
 * Delete all show formats for the given show.
 * 
 * @param int $show_id
 */
function delete_show_formats( $show_id = NULL ) {
	$show_formats = get_show_formats();
	unset( $show_formats[ $show_id ] );
	update_option( '_podlove_show_formats', $show_formats );
}

/**
 * Set format ids for the given show.
 * 
 * @param int $show_id
 * @param array $format_ids
 */
function update_show_formats( $show_id, $format_ids ) {
	$show_formats = get_show_formats();
	$show_formats[ $show_id ] = $format_ids;
	update_option( '_podlove_show_formats', $show_formats );
}

/**
 * Shorthand translation function.
 * 
 * @param string $text
 * @return string
 */
function t( $text ) {
	return __( $text, 'podlove' );
}

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
