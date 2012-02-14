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
			array( 'name' => 'MP3 Audio',  'type' => 'audio', 'mime_type' => 'audio/mpeg',  'extension' => 'mp3' ),
			array( 'name' => 'MPG Video',  'type' => 'video', 'mime_type' => 'video/mpeg',  'extension' => 'mpg' ),
			array( 'name' => 'MP4 Audio',  'type' => 'audio', 'mime_type' => 'audio/mp4',   'extension' => 'mp4' ),
			array( 'name' => 'MP4 Video',  'type' => 'video', 'mime_type' => 'video/mp4',   'extension' => 'mp4' ),
			array( 'name' => 'OGG Audio',  'type' => 'audio', 'mime_type' => 'audio/ogg',   'extension' => 'ogg' ),
			array( 'name' => 'OGG Video',  'type' => 'video', 'mime_type' => 'video/ogg',   'extension' => 'ogg' ),
			array( 'name' => 'WebM Audio', 'type' => 'audio', 'mime_type' => 'audio/webm',  'extension' => 'webm' ),
			array( 'name' => 'WebM Video', 'type' => 'video', 'mime_type' => 'video/webm',  'extension' => 'webm' ),
		);
		
		foreach ( $default_formats as $format ) {
			$f = new Model\Format;
			foreach ( $format as $key => $value ) {
				$f->{$key} = $value;
			}
			$f->save();
		}
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
