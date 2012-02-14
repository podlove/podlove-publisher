<?php
namespace Podlove;

register_activation_hook(   PLUGIN_FILE, __NAMESPACE__ . '\activate' );
register_deactivation_hook( PLUGIN_FILE, __NAMESPACE__ . '\deactivate' );
register_uninstall_hook(    PLUGIN_FILE, __NAMESPACE__ . '\uninstall' );

function activate() {
	Model\Feed::build();
	Model\Format::build();
	Model\Show::build();
	
	if ( ! Model\Format::has_entries() ) {
		$default_formats = array(
			array( 'name' => 'MP3 Audio',  'slug' => 'mp3-audio', 'type' => 'audio', 'mime_type' => 'audio/mpeg',  'extension' => 'mp3' ),
			array( 'name' => 'MPG Video',  'slug' => 'mpg-video', 'type' => 'video', 'mime_type' => 'video/mpeg',  'extension' => 'mpg' ),
			array( 'name' => 'MP4 Audio',  'slug' => 'mp4-audio', 'type' => 'audio', 'mime_type' => 'audio/mp4',   'extension' => 'mp4' ),
			array( 'name' => 'MP4 Video',  'slug' => 'mp4-video', 'type' => 'video', 'mime_type' => 'video/mp4',   'extension' => 'mp4' ),
			array( 'name' => 'OGG Audio',  'slug' => 'ogg-audio', 'type' => 'audio', 'mime_type' => 'audio/ogg',   'extension' => 'ogg' ),
			array( 'name' => 'OGG Video',  'slug' => 'ogg-video', 'type' => 'video', 'mime_type' => 'video/ogg',   'extension' => 'ogg' ),
			array( 'name' => 'WebM Audio', 'slug' => 'wbm-audio', 'type' => 'audio', 'mime_type' => 'audio/webm',  'extension' => 'webm' ),
			array( 'name' => 'WebM Video', 'slug' => 'wbm-video', 'type' => 'video', 'mime_type' => 'video/webm',  'extension' => 'webm' ),
		);
		
		foreach ( $default_formats as $format ) {
			$f = new Model\Format;
			foreach ( $format as $key => $value ) {
				$f->{$key} = $value;
			}
			$f->save();
		}
	}
	
	if ( ! Model\Show::has_entries() ) {
		$show                        = new Model\Show;
		$show->name                  = \Podlove\t( 'My Podcast' );
		$show->slug                  = \Podlove\t( 'my-podcast' );
		$show->subtitle              = \Podlove\t( 'I can haz listeners?' );
		$show->owner_email           = get_bloginfo( 'admin_email' );
		$show->explicit              = false;
		$show->url_delimiter         = '-';
		$show->episode_number_length = 3;
		$show->save();
		
		$feed                   = new Model\Feed;
		$feed->show_id          = $show->id;
		$feed->format_id        = Model\Format::find_one_by_name( 'MP3 Audio' )->id;
		$feed->name             = \Podlove\t( 'My Awesome Podcast Feed (MP3)' );
		$feed->title            = \Podlove\t( 'My Awesome Podcast Feed' );
		$feed->slug             = \Podlove\t( 'my-awesome-podcast-feed' );
		$feed->block            = false;
		$feed->discoverable     = true;
		$feed->show_description = true;
		$feed->save();
	}
}

function deactivate() {

}

function uninstall() {
	Model\Feed::destroy();
	Model\Format::destroy();
	Model\Show::destroy();
}

add_action( 'init', function () {
	new Podcast_Post_Type();
});

// ==============================================
// = EXPERIMENTAL (UNFUNCTIONAL) BOCKMIST STUFF =
// ==============================================

add_filter('query_vars', function ( $qv ) {
	$qv[] = 'show_slug';
	$qv[] = 'feed_slug';
	return $qv;
} );

// FIXME DON'T DO THIS EVERY TIME
// add_action( 'admin_init', 'flush_rewrite_rules' );

// The following defines a rule that maps URLs like /geostate/oregon to a URL request like ?geostate=oregon
add_action('generate_rewrite_rules', '\Podlove\add_rewrite_rules');

function add_rewrite_rules( $wp_rewrite ) {
	$new_rules = array( 
		'feed/(.+)/(.+)' => 'index.php?show_slug=' . $wp_rewrite->preg_index( 1 ) . '&amp;feed_slug=' . $wp_rewrite->preg_index( 2 )
	);
	$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
}