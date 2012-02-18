<?php
namespace Podlove;

register_activation_hook(   PLUGIN_FILE, __NAMESPACE__ . '\activate' );
register_deactivation_hook( PLUGIN_FILE, __NAMESPACE__ . '\deactivate' );
register_uninstall_hook(    PLUGIN_FILE, __NAMESPACE__ . '\uninstall' );
add_action( 'wpmu_new_blog', '\Podlove\create_new_blog', 10, 6);

function activate_for_current_blog() {
	Model\Feed::build();
	Model\Format::build();
	Model\Show::build();
	
	if ( ! Model\Format::has_entries() ) {
		$default_formats = array(
			array( 'name' => 'MP3 Audio',  'suffix' => '-legacy',    'type' => 'audio', 'mime_type' => 'audio/mpeg',  'extension' => 'mp3' ),
			array( 'name' => 'MPG Video',  'suffix' => '-legacy',    'type' => 'video', 'mime_type' => 'video/mpeg',  'extension' => 'mpg' ),
			array( 'name' => 'MP4 Audio',  'suffix' => '-modern',    'type' => 'audio', 'mime_type' => 'audio/mp4',   'extension' => 'm4a' ),
			array( 'name' => 'MP4 Video',  'suffix' => '-modern',    'type' => 'video', 'mime_type' => 'video/mp4',   'extension' => 'm4v' ),
			array( 'name' => 'OGG Audio',  'suffix' => '-oldschool', 'type' => 'audio', 'mime_type' => 'audio/ogg',   'extension' => 'oga' ),
			array( 'name' => 'OGG Video',  'suffix' => '-oldschool', 'type' => 'video', 'mime_type' => 'video/ogg',   'extension' => 'ogv' ),
			array( 'name' => 'WebM Audio', 'suffix' => '-chrome-audio',    'type' => 'audio', 'mime_type' => 'audio/webm',  'extension' => 'webm' ),
			array( 'name' => 'WebM Video', 'suffix' => '-chrome-video',    'type' => 'video', 'mime_type' => 'video/webm',  'extension' => 'webm' ),
		);
		
		foreach ( $default_formats as $format ) {
			$f = new Model\Format;
			foreach ( $format as $key => $value ) {
				$f->{$key} = $value;
			}
			$f->save();
		}
	}
	
	// if ( ! Model\Show::has_entries() ) {
	// 	$show                        = new Model\Show;
	// 	$show->name                  = \Podlove\t( 'My Podcast' );
	// 	$show->slug                  = \Podlove\t( 'my-podcast' );
	// 	$show->subtitle              = \Podlove\t( 'I can haz listeners?' );
	// 	$show->owner_email           = get_bloginfo( 'admin_email' );
	// 	$show->explicit              = false;
	// 	$show->url_delimiter         = '-';
	// 	$show->episode_number_length = 3;
	// 	$show->save();
	// 	
	// 	$feed                   = new Model\Feed;
	// 	$feed->show_id          = $show->id;
	// 	$feed->format_id        = Model\Format::find_one_by_name( 'MP3 Audio' )->id;
	// 	$feed->name             = \Podlove\t( 'My Awesome Podcast Feed (MP3)' );
	// 	$feed->title            = \Podlove\t( 'My Awesome Podcast Feed' );
	// 	$feed->slug             = \Podlove\t( 'my-awesome-podcast-feed' );
	// 	$feed->block            = false;
	// 	$feed->discoverable     = true;
	// 	$feed->show_description = true;
	// 	$feed->save();
	// }
}

/**
 * Hook: Create a new blog in a multisite environment.
 * 
 * When a new blog is created, we have to trigger the activation function
 * for in the scope of that blog.
 */
function create_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	global $wpdb;
	
	// something like 'podlove/podlove.php'
	$plugin_file = basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ );
    
	if ( is_plugin_active_for_network( $plugin_file ) ) {
		$current_blog = $wpdb->blogid;
		switch_to_blog( $blog_id );
		activate_for_current_blog();
		switch_to_blog( $current_blog );
	}
}

/**
 * Hook: Activate the plugin.
 * 
 * In a single blog install, just call activate_for_current_blog().
 * However, in a multisite install, iterate over all blogs and call the activate
 * function for each of them.
 */
function activate() {
	global $wpdb;
	
	if ( is_multisite() ) {
		if ( isset( $_GET[ 'networkwide' ] ) && ( $_GET[ 'networkwide' ] == 1 ) ) {
            $current_blog = $wpdb->blogid;
			$blogids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs" ) );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog($blog_id);
				activate_for_current_blog();
			}
			switch_to_blog($current_blog);
		} else {
			activate_for_current_blog();
		}
	} else {
		activate_for_current_blog();
	}
	
}

function deactivate() {

}

/**
 * Hook: Uninstall the plugin.
 * 
 * In a single blog install, just call uninstall_for_current_blog().
 * However, in a multisite install, iterate over all blogs and call the 
 * uninstall function for each of them.
 */
function uninstall() {
	global $wpdb;
	
	if ( is_multisite() ) {
		if ( isset( $_GET[ 'networkwide' ] ) && ( $_GET[ 'networkwide' ] == 1 ) ) {
            $current_blog = $wpdb->blogid;
			$blogids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs" ) );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog($blog_id);
				uninstall_for_current_blog();
			}
			switch_to_blog($current_blog);
		} else {
			activate_for_current_blog();
		}
	} else {
		uninstall_for_current_blog();
	}
}

function uninstall_for_current_blog() {
	Model\Feed::destroy();
	Model\Format::destroy();
	Model\Show::destroy();
}

add_action( 'init', function () {
	new Podcast_Post_Type();
});
