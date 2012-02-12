<?php

/**
 * Custom Post Type
 */
class Podcast_Post_Type {
	
	/**
	 * Register custom "podcast" post type.
	 */
	public function __construct() {
		$labels = array(
			'name'               => Podlove::t( 'Podcast' ),
			'singular_name'      => Podlove::t( 'Podcast' ),
			'add_new'            => Podlove::t( 'Add New' ),
			'add_new_item'       => Podlove::t( 'Add New Episode' ),
			'edit_item'          => Podlove::t( 'Edit Episode' ),
			'new_item'           => Podlove::t( 'New Episode' ),
			'all_items'          => Podlove::t( 'All Episodes' ),
			'view_item'          => Podlove::t( 'View Episode' ),
			'search_items'       => Podlove::t( 'Search Episodes' ),
			'not_found'          => Podlove::t( 'No episodes found' ),
			'not_found_in_trash' => Podlove::t( 'No episodes found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => Podlove::t( 'Podcasts' ),
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
		
		$args = apply_filters( 'podlove_post_type_args', $args );
		
		register_post_type( 'podcast', $args );
		add_action( 'save_post', array( $this, 'save_postdata' ) );
		
		require_once 'abstract-taxonomy.php';
		$this->register_formats_taxonomy();
		$this->register_feeds_taxonomy();
		$this->register_shows_taxonomy();
		
		// add custom rss2 feed for iTunes
		// remove_all_actions( 'do_feed_rss2' );
		// add_action( 'do_feed_rss2', array( $this, 'add_itunes_rss_feed' ) );
		
		if ( is_admin() ) {
			add_action( 'podlove_list_shows', array( $this, 'list_shows' ) );
			
			wp_register_script(
				'podlove_admin_script',
				WP_PLUGIN_URL . 'podlove/js/admin.js',
				array('jquery'),
				'1.0' 
			);
			wp_enqueue_script( 'podlove_admin_script' );
		}
		
		
		add_filter( 'request', array( $this, 'add_post_type_to_feeds' ) );
		add_action( 'atom_entry', array( $this, 'add_itunes_atom_fields' ) );
		add_action( 'atom_ns', array( $this, 'add_itunes_atom_dtd' ) );
		
		// get out the crowbar: enforce atom feed by redirecting all to atom
		// DISCUSS: cool? not cool?
		remove_all_actions( 'do_feed_rss2' );
		add_action( 'do_feed_rss2', array( $this, 'replace_rss_with_atom' ) );
		remove_all_actions( 'do_feed_rss' );
		add_action( 'do_feed_rss', array( $this, 'replace_rss_with_atom' ) );
		remove_all_actions( 'do_feed_rdf' );
		add_action( 'do_feed_rdf', array( $this, 'replace_rss_with_atom' ) );
	}

	private function register_formats_taxonomy() {
		require_once 'formats-taxonomy.php';
		new Podlove_File_Formats_Taxonomy();
	}
	
	private function register_feeds_taxonomy() {
		require_once 'feeds-taxonomy.php';
		new Podlove_Feeds_Taxonomy();
	}
	
	private function register_shows_taxonomy() {
		require_once 'shows-taxonomy.php';
		new Podlove_Shows_Taxonomy();
	}
	
	public function replace_rss_with_atom( $is_comment_feed ) {
		do_feed_atom( $is_comment_feed );
	}
	
	public function add_itunes_atom_dtd() {
		?>
		xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
		<?php
	}
	
	public function add_itunes_atom_fields() {
		$meta = $this->get_meta();
		?>
		<?php if ( $meta[ 'duration' ] ): ?>
			<itunes:duration><?php echo $meta[ 'duration' ]; ?></itunes:duration>
		<?php endif; ?>
		<?php if ( $meta[ 'enclosure_url' ] && $meta[ 'byte_length' ] ): ?>
			<link href="<?php echo $meta[ 'enclosure_url' ]; ?>" rel="enclosure" length="<?php echo (int) $meta[ 'byte_length' ]; ?>" type="audio/mpeg"/>
		<?php endif; ?>		
		<?php
	}
	
	/**
	 * Add Custom Post Type to all WordPress Feeds.
	 * 
	 * @param array $query_var
	 * @return array
	 */
	function add_post_type_to_feeds( $query_var ) {
		if ( isset( $query_var[ 'feed' ] ) ) {

			$extend = array(
				'post' => 'post',
				'podcast' => 'podcast'
			);

			if ( empty( $query_var[ 'post_type' ] ) ) {
				$query_var[ 'post_type' ] = $extend;
			} else {
				$query_var[ 'post_type' ] = array_merge( $query_var[ 'post_type' ], $extend );
			}
		}

		return $query_var;
	}
	
	/**
	 * http://your-wordpress-domain.com/feed?post_type=podcast
	 */
	// public function add_itunes_rss_feed( $is_comment_feed ) {
	// 	$rss_template = plugin_dir_path( __FILE__ ) . '/feed-rss2.php';
	// 	if ( get_query_var( 'post_type' ) == 'podcast' && file_exists( $rss_template ) )
	// 		load_template( $rss_template );
	// 	else
	// 		do_feed_rss2( $is_comment_feed );
	// }
	
	/**
	 * Register post meta boxes.
	 */
	public function register_post_type_meta_boxes() {
		add_meta_box(
			/* $id            */ 'podlove',
			/* $title         */ Podlove::t( 'Podcast Episode' ),
			/* $callback      */ array( $this, 'post_type_meta_box_callback' ),
			/* $page          */ 'podcast'
			/* $context       */ 
			/* $priority      */ 
			/* $callback_args */ 
		);
	}
	
	/**
	 * Fetch post meta and set sensible defaults.
	 * 
	 * @return array
	 */
	private function get_meta() {
		global $post;
		
		$meta = get_post_meta( $post->ID, '_podlove_meta', true );
		
		if ( ! is_array( $meta ) )
			$meta = array();
		
		$defaults = array(
			'id'          => NULL,
			'title'       => NULL,
			'slug'        => NULL,
			'subtitle'    => NULL,
			'summary'     => NULL,
			'description' => NULL,
			'timeline'    => NULL
		);
		
		return array_merge( $defaults, $meta );
	}
	
	public function list_shows() {
		global $post;
		$shows        = get_terms( 'podcast_shows', array( 'hide_empty' => false ) );
		$active_shows = get_the_terms( $post->ID, 'podcast_shows' );
		$active_slugs = array_map( 'podlove_map_slugs', $active_shows );
		?>
		<tr valign="top">
			<th scope="row">
				<label for="podcast_shows"><?php echo Podlove::t( 'Show' ); ?></label>
			</th>
			<td>
				<?php foreach ( $shows as $show ): ?>
					<?php $id = 'podcast_show_' . $show->term_id ?>
					<input type="checkbox" name="<?php echo $id; ?>" id="<?php echo $id; ?>" class="podcast_show_checkbox" data-slug="<?php echo $show->slug; ?>" <?php if ( in_array( $show->slug, $active_slugs ) ): ?>checked="checked"<?php endif; ?>>
					<label for="<?php echo $id; ?>"><?php echo $show->name; ?></label>
					<br/>
				<?php endforeach; ?>
			</td>
		</tr>
		<?php
	}
	
	/**
	 * Meta Box Template
	 */
	public function post_type_meta_box_callback() {
		$meta = $this->get_meta();
		wp_nonce_field( plugin_basename( __FILE__ ), 'podlove_noncename' );
		?>
		<table class="form-table">
			<?php do_action( 'podlove_list_shows' ) ?>
			<?php foreach ( $meta as $key => $value ): ?>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo $key; ?>"><?php echo Podlove::t( $key ); ?></label>
					</th>
					<td>
						<input type="text" name="podlove_meta[<?php echo $key; ?>]" value="<?php echo $value; ?>" id="<?php echo $key; ?>">
					</td>
				</tr>				
			<?php endforeach; ?>
		</table>
		<?php
	}
	
	public function save_postdata( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;
		
		if ( empty( $_POST[ 'podlove_noncename' ] ) || ! wp_verify_nonce( $_POST[ 'podlove_noncename' ], plugin_basename( __FILE__ ) ) )
			return;
		
		// Check permissions
		if ( 'podcast' == $_POST['post_type'] ) {
		  if ( ! current_user_can( 'edit_post', $post_id ) )
			return;
		} else {
			return;
		}

		update_post_meta( $post_id, '_podlove_meta', $_POST[ 'podlove_meta' ] );
	}
}