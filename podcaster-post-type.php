<?php

/**
 * Custom Post Type
 */
class Podcaster_Post_Type {
	
	/**
	 * Register custom "podcast" post type.
	 */
	public function __construct() {
		$labels = array(
			'name'               => Podcaster::t( 'Podcast' ),
			'singular_name'      => Podcaster::t( 'Podcast' ),
			'add_new'            => Podcaster::t( 'Add New' ),
			'add_new_item'       => Podcaster::t( 'Add New Episode' ),
			'edit_item'          => Podcaster::t( 'Edit Episode' ),
			'new_item'           => Podcaster::t( 'New Episode' ),
			'all_items'          => Podcaster::t( 'All Episodes' ),
			'view_item'          => Podcaster::t( 'View Episode' ),
			'search_items'       => Podcaster::t( 'Search Episodes' ),
			'not_found'          => Podcaster::t( 'No episodes found' ),
			'not_found_in_trash' => Podcaster::t( 'No episodes found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => Podcaster::t( 'Podcasts' ),
		);
		
		$args = array(
			'labels'               => $labels,
			'public'               => true,
			'publicly_queryable'   => true,
			'show_ui'              => true, 
			'show_in_menu'         => true, 
			'query_var'            => true,
			'rewrite'              => true,
			'capability_type'      => 'post',
			'has_archive'          => true, 
			'supports'             => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'trackbacks' ),
			'register_meta_box_cb' => array( $this, 'register_post_type_meta_boxes' )
		); 
		
		$args = apply_filters( 'podcaster_post_type_args', $args );
		
		register_post_type( 'podcast', $args );
		add_action( 'save_post', array( $this, 'save_postdata' ) );
		
		// add custom rss2 feed for iTunes
		remove_all_actions( 'do_feed_rss2' );
		add_action( 'do_feed_rss2', array( $this, 'add_itunes_rss_feed' ), 10, 1 );
	}
	
	/**
	 * http://your-wordpress-domain.com/feed?post_type=podcast
	 */
	public function add_itunes_rss_feed( $default ) {
		$rss_template = plugin_dir_path( __FILE__ ) . '/feed-rss2.php';
		if ( get_query_var( 'post_type' ) == 'podcast' && file_exists( $rss_template ) )
			load_template( $rss_template );
		else
			do_feed_rss2( $default );
	}
	
	/**
	 * Register post meta boxes.
	 */
	public function register_post_type_meta_boxes() {
		add_meta_box(
			/* $id            */ 'podcaster',
			/* $title         */ Podcaster::t( 'Podcast Metadata' ),
			/* $callback      */ array( $this, 'post_type_meta_box_callback' ),
			/* $page          */ 'podcast'
			/* $context       */ 
			/* $priority      */ 
			/* $callback_args */ 
		);
	}
	
	public function post_type_meta_box_callback() {
		global $post;

		$meta = get_post_meta( $post->ID, 'podcaster_meta', true );
		
		wp_nonce_field( plugin_basename( __FILE__ ), 'podcaster_noncename' );
		?>
		<input type="text" name="podcaster_meta[duration]" value="<?php echo $meta[ 'duration' ]; ?>" />
		<input type="text" name="podcaster_meta[foo]" value="<?php echo $meta[ 'foo' ]; ?>" />
		<input type="text" name="podcaster_meta[bar]" value="<?php echo $meta[ 'bar' ]; ?>" />
		<?php
	}
	
	public function save_postdata( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;
		
		if ( empty( $_POST[ 'podcaster_noncename' ] ) || ! wp_verify_nonce( $_POST[ 'podcaster_noncename' ], plugin_basename( __FILE__ ) ) )
			return;
		
		// Check permissions
		if ( 'podcast' == $_POST['post_type'] ) {
		  if ( ! current_user_can( 'edit_post', $post_id ) )
			return;
		} else {
			return;
		}

		update_post_meta( $post_id, 'podcaster_meta', $_POST[ 'podcaster_meta' ] );
	}
}