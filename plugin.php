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
			// @TODO slug => format_suffix
			array( 'name' => 'MP3 Audio',  'slug' => '-legacy',    'type' => 'audio', 'mime_type' => 'audio/mpeg',  'extension' => 'mp3' ),
			array( 'name' => 'MPG Video',  'slug' => '-legacy',    'type' => 'video', 'mime_type' => 'video/mpeg',  'extension' => 'mpg' ),
			array( 'name' => 'MP4 Audio',  'slug' => '-modern',    'type' => 'audio', 'mime_type' => 'audio/mp4',   'extension' => 'm4a' ),
			array( 'name' => 'MP4 Video',  'slug' => '-modern',    'type' => 'video', 'mime_type' => 'video/mp4',   'extension' => 'm4v' ),
			array( 'name' => 'OGG Audio',  'slug' => '-oldschool', 'type' => 'audio', 'mime_type' => 'audio/ogg',   'extension' => 'oga' ),
			array( 'name' => 'OGG Video',  'slug' => '-oldschool', 'type' => 'video', 'mime_type' => 'video/ogg',   'extension' => 'ogv' ),
			array( 'name' => 'WebM Audio', 'slug' => '-chrome-audio',    'type' => 'audio', 'mime_type' => 'audio/webm',  'extension' => 'webm' ),
			array( 'name' => 'WebM Video', 'slug' => '-chrome-video',    'type' => 'video', 'mime_type' => 'video/webm',  'extension' => 'webm' ),
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

add_filter( 'query_vars', function ( $qv ) {
	$qv[] = 'show_slug';
	$qv[] = 'feed_slug';
	return $qv;
} );

// Hooks:
// parse_query => query vars available
// wp => query_posts done
add_action( 'wp', function () {
	global $wp_query;
	
	$show_slug = get_query_var( 'show_slug' );
	$feed_slug = get_query_var( 'feed_slug' );
	
	if ( ! $show_slug || ! $feed_slug )
		return;
	
	// @fixme either slugs are unique or we need to check for id or something
	$show = \Podlove\Model\Show::find_one_by_slug( $show_slug );
	$feed = \Podlove\Model\Feed::find_one_by_slug( $feed_slug );
 
	add_action( 'rss2_item', function () use ( $show, $feed ) {
		global $post;
		
		$meta   = get_post_meta( $post->ID, '_podlove_meta', true );
		$format = \Podlove\Model\Format::find_by_id( $feed->format_id );
		
		$url  = $show->media_file_base_uri;
		$url .= $meta[ $show->id ][ 'file_slug' ];
		$url .= $format->slug;
		$url .= '.';
		$url .= $format->extension;
		?>
		<enclosure url="<?php echo $url; ?>" length="0" type="<?php echo $format->mime_type; ?>" />
		<?php
	} );

	$args = array(
		'post_type'=> 'podcast'
	);
	query_posts( $args );
	do_action( 'do_feed_rss2', $wp_query->is_comment_feed );
	exit;
} );

// FIXME DON'T DO THIS EVERY TIME
add_action( 'admin_init', 'flush_rewrite_rules' );

// The following defines a rule that maps URLs like /geostate/oregon to a URL request like ?geostate=oregon
add_action( 'generate_rewrite_rules', function ( $wp_rewrite ) {
	file_put_contents('/tmp/php.log', print_r("\n" . "never?", true), FILE_APPEND | LOCK_EX);
	$new_rules = array( 
		'feed/(.+)/(.+)' => 'index.php?show_slug=' . $wp_rewrite->preg_index( 1 ) . '&feed_slug=' . $wp_rewrite->preg_index( 2 )
	);
	$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
} );

