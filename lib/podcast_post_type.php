<?php

namespace Podlove;

/**
 * Custom Post Type
 */
class Podcast_Post_Type {
	
	/**
	 * Register custom "podcast" post type.
	 */
	public function __construct() {
		
		$this->form_data = array(
			'enable_show' => array(
				'label'       => \Podlove\t( 'Enable Show' ),
				'description' => '',
				'args' => array(
					'type'     => 'checkbox',
					'default'  => true
				)
			),
			'file_slug' => array(
				'label'       => \Podlove\t( 'Episode File Slug' ),
				'description' => ''
			),
			'formats' => array(
				'label'       => \Podlove\t( 'File Formats' ),
				'description' => '',
				'args' => array(
					'type'    => 'multiselect',
					'options' => NULL, // requires $show context
					'default' => true
				)
			),
			'duration' => array(
				'label'       => \Podlove\t( 'Duration' ),
				'description' => ''
			),
			'file_size' => array(
				'label'       => \Podlove\t( 'File Size' ),
				'description' => ''
			),
			'cover_art_url' => array(
				'label'       => \Podlove\t( 'Cover Art URL' ),
				'description' => \Podlove\t( 'JPEG or PNG. At least 600 x 600 pixels.' )
			),
			'block' => array(
				'label'       => \Podlove\t( 'Block?' ),
				'description' => \Podlove\t( 'Forbid iTunes to list this episode.' ),
				'args' => array(
					'type'     => 'checkbox',
					'default'  => false
				)
			),
		);
		
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
		
		\Podlove\Feeds\init();
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
		$shows = \Podlove\Model\Show::all();
		foreach ( $shows as $show ) {
			add_meta_box(
				/* $id            */ 'podlove_show_' . $show->id,
				/* $title         */ \Podlove\t( 'Podcast: ' . $show->full_title() ),
				/* $callback      */ array( $this, 'post_type_meta_box_callback' ),
				/* $page          */ 'podcast',
				/* $context       */ 'advanced',
				/* $priority      */ 'default',
				/* $callback_args */ array( $show )
			);
		}
	}
	
	/**
	 * Meta Box Template
	 */
	public function post_type_meta_box_callback( $post, $args ) {
		$show = $args[ 'args' ][ 0 ];
		$all_meta = $this->get_meta();
		$meta = $all_meta[ $show->id ];
		
		$format_options = array();
		$feeds = \Podlove\Model\Feed::find_all_by_show_id( $show->id );
		foreach ( $feeds as $feed ) {
			$format_options[ $feed->format_id ] = \Podlove\Model\Format::find_by_id( $feed->format_id )->name;
		}
		$this->form_data[ 'formats' ][ 'args' ][ 'options' ] = $format_options;
		
		wp_nonce_field( plugin_basename( __FILE__ ), 'podlove_noncename' );
		?>
		<table class="form-table">
			<?php foreach ( $this->form_data as $key => $value ): ?>
				<?php \Podlove\Form\input( '_podlove_meta[' . $show->id . ']', $meta[ $key ], $key, $value ); ?>
			<?php endforeach; ?>
		</table>
		<?php
	}
	
	/**
	 * Fetch post meta and set sensible defaults.
	 * 
	 * @return array
	 */
	private function get_meta() {
		global $post;
		
		$defaults = array();
		foreach ( $this->form_data as $key => $value ) {
			$defaults[ $key ] = NULL;
			if ( isset( $value[ 'args' ] ) && isset( $value[ 'args' ][ 'default' ] ) )
				$defaults[ $key ] = $value[ 'args' ][ 'default' ];
		}
		
		$meta = get_post_meta( $post->ID, '_podlove_meta', true );
		
		if ( ! is_array( $meta ) )
			$meta = array();
		
		$shows = \Podlove\Model\Show::all();
		foreach ( $shows as $show ) {
			if ( ! isset( $meta[ $show->id ] ) ) {
				$meta[ $show->id ] = array();
			}
				
			$meta[ $show->id ] = array_merge( $defaults, $meta[ $show->id ] );
		}
		
		return $meta;
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

		if ( ! isset( $_POST[ '_podlove_meta' ] ) || ! is_array( $_POST[ '_podlove_meta' ] ) )
			return;
		
		// What do we needs these loops for?
		// When you submit a checkbox, the value is "on" when active.
		// However, when unchecked, nothing is send at all. So, to determine
		// the difference between "new" and "unchecked", we populate all unset
		// fields with false manually.
		$formats = array_map( function( $f ) { return $f->id; }, \Podlove\Model\Format::all() );
		foreach ( $_POST[ '_podlove_meta' ] as $show_id => $_ ) {
			foreach ( $this->form_data as $key => $value ) {
				if ( ! isset( $_POST[ '_podlove_meta' ][ $show_id ][ $key ] ) )
					$_POST[ '_podlove_meta' ][ $show_id ][ $key ] = false;
			}
			foreach ( $formats as $format_id ) {
				if ( ! isset( $_POST[ '_podlove_meta' ][ $show_id ][ 'formats' ][ $format_id ] ) )
					$_POST[ '_podlove_meta' ][ $show_id ][ 'formats' ][ $format_id ] = false;
			}
		}

		update_post_meta( $post_id, '_podlove_meta', $_POST[ '_podlove_meta' ] );
	}
}