<?php

namespace Podlove;

/**
 * Custom Post Type
 * 
 * @todo move formats, feeds, shows menu items into separate menu item
 */
class Podcast_Post_Type {
	
	/**
	 * Register custom "podcast" post type.
	 */
	public function __construct() {
		$labels = array(
			'name'               => \Podlove\t( 'Episodes' ),
			'singular_name'      => \Podlove\t( 'Episode' ),
			'add_new'            => \Podlove\t( 'Add New' ),
			'add_new_item'       => \Podlove\t( 'Add New Episode' ),
			'edit_item'          => \Podlove\t( 'Edit Episode' ),
			'new_item'           => \Podlove\t( 'New Episode' ),
			'all_items'          => \Podlove\t( 'All Episodes' ),
			'view_item'          => \Podlove\t( 'View Episode' ),
			'search_items'       => \Podlove\t( 'Search Episodes' ),
			'not_found'          => \Podlove\t( 'No episodes found' ),
			'not_found_in_trash' => \Podlove\t( 'No episodes found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => \Podlove\t( 'Episodes' ),
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
		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		
		if ( is_admin() ) {
			add_action( 'podlove_list_shows', array( $this, 'list_shows' ) );
			add_action( 'podlove_list_formats', array( $this, 'list_formats' ) );
			
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
		// remove_all_actions( 'do_feed_rss2' );
		// add_action( 'do_feed_rss2', array( $this, 'replace_rss_with_atom' ) );
		remove_all_actions( 'do_feed_rss' );
		add_action( 'do_feed_rss', array( $this, 'replace_rss_with_atom' ) );
		remove_all_actions( 'do_feed_rdf' );
		add_action( 'do_feed_rdf', array( $this, 'replace_rss_with_atom' ) );
		
		add_action( 'rss2_head', array( $this, 'extend_rss2_head' ) );
	}
	
	function extend_rss2_head() {
		# code...
	}
	
	public function create_menu() {
		$handle = 'podlove_settings_handle';
		
		// create new top-level menu
		$hook = add_menu_page(
			/* $page_title */ 'Podlove Plugin Settings',
			/* $menu_title */ 'Podlove',
			/* $capability */ 'administrator',
			/* $menu_slug  */ $handle,
			/* $function   */ array( $this, 'settings_page' ),
			/* $icon_url   */ PLUGIN_URL . '/images/podlove-icon-16x16.png'
			/* $position   */
		);
		
		new \Podlove\Settings\Format( $handle );
		new \Podlove\Settings\Show( $handle );
	}
	
	public function settings_page() {
		?>
		Work in Progress ...
		<?php
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
	 * Register post meta boxes.
	 */
	public function register_post_type_meta_boxes() {
		add_meta_box(
			/* $id            */ 'podlove_show',
			/* $title         */ \Podlove\t( 'Podcast Episode' ),
			/* $callback      */ array( $this, 'post_type_meta_box_callback' ),
			/* $page          */ 'podcast',
			/* $context       */ 'advanced',
			/* $priority      */ 'default'
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
			'show_id' => NULL
		);
		
		return array_merge( $defaults, $meta );
	}
	
	/**
	 * Meta Box Template
	 */
	public function post_type_meta_box_callback( $post, $args ) {
		$meta = $this->get_meta();
		$raw_shows = \Podlove\Model\Show::all();
		$shows = array();
		foreach ( $raw_shows as $show ) {
			$shows[ $show->id ] = $show->name;
		}
		
		$form_data = array(
			'show_id' => array(
				'label'       => \Podlove\t( 'Select Show' ),
				'description' => '',
				'args' => array(
					'type'     => 'select',
					'options'  => $shows
				)
			)
		);
		
		wp_nonce_field( plugin_basename( __FILE__ ), 'podlove_noncename' );
		?>
		<table class="form-table">
			<?php foreach ( $form_data as $key => $value ): ?>
				<?php \Podlove\Form\input( '_podlove_meta', $meta[ $key ], $key, $value ); ?>
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

		update_post_meta( $post_id, '_podlove_meta', $_POST[ '_podlove_meta' ] );
	}
}