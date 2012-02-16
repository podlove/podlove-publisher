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
	$show   = \Podlove\Model\Show::find_one_by_slug( $show_slug );
	$feed   = \Podlove\Model\Feed::find_one_by_slug( $feed_slug );
	$format = \Podlove\Model\Format::find_by_id( $feed->format_id );

	// mute bloginfo_rss( 'name' ) 
	add_filter( 'bloginfo_rss', function ( $value, $key ) {
		return apply_filters( 'podlove_rss2_title_name', ( $key == 'name' ) ? '' : $value );
	}, 10, 2 );
	
	// override feed title
	add_filter( 'wp_title_rss', function ( $title ) use ( $feed ) {
		return apply_filters( 'podlove_rss2_title', $feed->title );
	} );

	add_filter( 'option_rss_language', function ( $language ) use ( $feed ) {
		return apply_filters( 'podlove_rss2_language', ( $feed->language ) ? $feed->language : $language );
	} );

	add_action( 'rss2_head', function () use ( $show, $feed, $format ) {
		$author = sprintf( '<itunes:author>%s</itunes:author>', $show->author_name );
		echo apply_filters( 'podlove_rss2_itunes_author', $author );
		
		$summary = sprintf( '<itunes:summary>%s</itunes:summary>', $show->summary );
		echo apply_filters( 'podlove_rss2_itunes_summary', $summary );
		
		$categories = \Podlove\Itunes\categories( false );
		
		$category_html = '';
		for ( $i = 1; $i <= 3; $i++ ) { 
			$category_id = $show->{'category_' . $i};
			
			if ( ! $category_id )
				continue;
			
			list( $cat, $subcat ) = explode( '-', $category_id );
			
			if ( $subcat == '00' ) {
				$category_html .= sprintf(
					'<itunes:category text="%s"></itunes:category>',
					htmlspecialchars( $categories[ $category_id ] )
				);
			} else {
				$category_html .= sprintf(
					'<itunes:category text="%s"><itunes:category text="%s"></itunes:category></itunes:category>',
					htmlspecialchars( $categories[ $cat . '-00' ] ),
					htmlspecialchars( $categories[ $category_id ] )
				);
			}
		}
		echo apply_filters( 'podlove_rss2_itunes_categories', $category_html );
	} );

	add_action( 'rss2_item', function () use ( $show, $feed, $format ) {
		global $post;
		
		$meta      = get_post_meta( $post->ID, '_podlove_meta', true );
		$show_meta = $meta[ $show->id ];

		$enclosure_file_size = isset( $show_meta[ 'file_size' ] ) ? $show_meta[ 'file_size' ] : 0;
		$enclosure_duration  = isset( $show_meta[ 'duration' ] ) ? $show_meta[ 'duration' ] : 0;
		$file_slug           = isset( $show_meta[ 'file_slug' ] ) ? $show_meta[ 'file_slug' ] : NULL;
		
		if ( ! $file_slug ) {
			// TODO might be a good idea to notify the podcast admin
		}
		
		$enclosure_url  = $show->media_file_base_uri;
		$enclosure_url .= $file_slug;
		$enclosure_url .= $format->slug;
		$enclosure_url .= '.';
		$enclosure_url .= $format->extension;
		
		$enclosure = sprintf( '<enclosure url="%s" length="%s" type="%s" />', $enclosure_url, $enclosure_file_size, $format->mime_type );
		echo apply_filters( 'podlove_rss2_enclosure', $enclosure );
		
		$duration = sprintf( '<itunes:duration>%s</itunes:duration>', $enclosure_duration );
		echo apply_filters( 'podlove_rss2_itunes_duration', $duration );
		
		$author = sprintf( '<itunes:author>%s</itunes:author>', $show->author_name );
		echo apply_filters( 'podlove_rss2_itunes_author', $author );
		
		$summary = sprintf( '<itunes:summary>%s</itunes:summary>', strip_tags( $post->post_excerpt ) );
		echo apply_filters( 'podlove_rss2_itunes_summary', $summary );
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
	$new_rules = array( 
		'feed/(.+)/(.+)' => 'index.php?show_slug=' . $wp_rewrite->preg_index( 1 ) . '&feed_slug=' . $wp_rewrite->preg_index( 2 )
	);
	$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
} );

