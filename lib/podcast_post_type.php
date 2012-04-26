<?php

namespace Podlove;

/**
 * Custom Post Type: "podcast"
 */
class Podcast_Post_Type {

	const SETTINGS_PAGE_HANDLE = 'podlove_settings_handle';
	
	public function __construct() {
		
		$this->form_data = array(
			'active' => array(
				'label'       => \Podlove\t( 'Post Episode to Show' ), // todo: hide/show rest of the form
				'description' => '',
				'type'     => 'checkbox',
				'default'  => true
			),
			// todo: add subtitle; but as extra metabox
			'slug' => array(
				'label'       => \Podlove\t( 'Episode File Slug' ),
				'description' => '',
				'html'        => array( 'class' => 'regular-text' )
			),
			'duration' => array(
				'label'       => \Podlove\t( 'Duration' ),
				'description' => '',
				'html'        => array( 'class' => 'regular-text' )
			),
			'cover_art' => array(
				'label'       => \Podlove\t( 'Episode Cover Art URL' ),
				'description' => \Podlove\t( 'JPEG or PNG. At least 600 x 600 pixels.' ),
				'html'        => array( 'class' => 'regular-text' )
			),
			'block' => array(
				'label'       => \Podlove\t( 'Block?' ),
				'description' => \Podlove\t( 'Forbid iTunes to list this episode.' ),
				'type'     => 'checkbox',
				'default'  => false
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
			'menu_position'        => 5, // below "Posts"
			'query_var'            => true,
			'rewrite'              => true,
			'capability_type'      => 'post',
			'has_archive'          => true, 
			'supports'             => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'trackbacks' ),
			'register_meta_box_cb' => array( $this, 'register_post_type_meta_boxes' ),
			'menu_icon'            => PLUGIN_URL . '/images/episodes-icon-16x16.png'
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
				WP_PLUGIN_URL . '/podlove/js/admin.js',
				array( 'jquery' ),
				'1.0' 
			);
			wp_enqueue_script( 'podlove_admin_script' );
		}
		
		add_filter( 'request', array( $this, 'add_post_type_to_feeds' ) );
		
		\Podlove\Feeds\init();
	}
		
	public function create_menu() {
		
		// create new top-level menu
		$hook = add_menu_page(
			/* $page_title */ 'Podlove Plugin Settings',
			/* $menu_title */ 'Podlove',
			/* $capability */ 'administrator',
			/* $menu_slug  */ self::SETTINGS_PAGE_HANDLE,
			/* $function   */ array( $this, 'settings_page' ),
			/* $icon_url   */ PLUGIN_URL . '/images/podlove-icon-16x16.png'
			/* $position   */
		);

		// rename first menu entry to "Dashboard"
		add_submenu_page(
			/* $parent_slug*/ self::SETTINGS_PAGE_HANDLE,
			/* $page_title */ \Podlove\t( 'Dashboard' ),
			/* $menu_title */ \Podlove\t( 'Dashboard' ),
			/* $capability */ 'administrator',
			/* $menu_slug  */ self::SETTINGS_PAGE_HANDLE,
			/* $function   */ array( $this, 'settings_page' )
		);
		
		new \Podlove\Settings\Format( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Show( self::SETTINGS_PAGE_HANDLE );
	}
	
	public function about_meta() {
		?>
		Podlove rocks 😸
		<?php
	}

	public function feed_overview_meta() {
		$table = new \Podlove\Feed_List_Table();
		$table->prepare_for_meta_box();
		$table->prepare_items();
		$table->display();
	}

	public function settings_page() {
		add_meta_box( self::SETTINGS_PAGE_HANDLE . '_about', \Podlove\t( 'About' ), array( $this, 'about_meta' ), self::SETTINGS_PAGE_HANDLE, 'side' );

		?>
		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>
			<h2><?php echo \Podlove\t( 'Podlove Dashboard' ); ?></h2>

			<div id="poststuff" class="metabox-holder has-right-sidebar">
				
				<!-- sidebar -->
				<div id="side-info-column" class="inner-sidebar">
					<?php do_action( 'podlove_settings_before_sidebar_boxes' ); ?>
					<?php do_meta_boxes( self::SETTINGS_PAGE_HANDLE, 'side', NULL ); ?>
					<?php do_action( 'podlove_settings_after_sidebar_boxes' ); ?>
				</div>

				<!-- main -->
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_action( 'podlove_settings_before_main_boxes' ); ?>
						<?php $this->feed_overview_meta(); ?>
						<?php do_meta_boxes( self::SETTINGS_PAGE_HANDLE, 'normal', NULL ); ?>
						<?php do_meta_boxes( self::SETTINGS_PAGE_HANDLE, 'additional', NULL ); ?>
						<?php do_action( 'podlove_settings_after_main_boxes' ); ?>						
					</div>
				</div>

				<br class="clear"/>

			</div>

		</div>
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
				/* $title         */ \Podlove\t( 'Show: ' ) . $show->name,
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
		$post_id = $post->ID;

		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );
		$release = \Podlove\Model\Release::find_or_create_by_episode_id_and_show_id( $episode->id, $show->id );

		// read / generate format data
		$media_locations = $show->media_locations();

		$location_values = array();
		$location_options = array();
		foreach ( $media_locations as $location ) {
			// get formats configured for this show
			$location_options[ $location->id ] = $location->media_format()->name;
			// find out which formats are active
			$location_values[ $location->id ] = NULL !== \Podlove\Model\MediaFile::find_by_release_id_and_media_location_id( $release->id, $location->id );
		}

		$formats_form = array(
			'label'       => \Podlove\t( 'File Formats' ),
			'description' => '',
			'type'    => 'multiselect',
			'options' => $location_options,
			'default' => true,
			'multiselect_callback' => function ( $location_id ) use ( $release, $show ) {
				$location = \Podlove\Model\MediaLocation::find_by_id( $location_id );
				$format   = $location->format();
				$feed     = \Podlove\Model\Feed::find_by_show_id_and_format_id( $show->id, $format->id );
				$file     = \Podlove\Model\File::find_by_release_id_and_format_id( $release->id, $format->id );
				$filesize = ( is_object( $file ) ) ? $file->size : 0;					
				return 'data-extension="' . $format->extension . '" data-suffix="' . $feed->suffix . '" data-size="' . $filesize . '"';
			}
		);

		if ( empty( $location_options ) ) {
			$formats_form[ 'description' ] = sprintf( '<span style="color: red">%s</span>', \Podlove\t( 'You need to configure feeds for this show. No feeds, no fun.' ) )
			                               . ' '
			                               . sprintf( '<a href="' . admin_url( 'admin.php?page=podlove_shows_settings_handle&action=edit&show=' . $show->id ) . '">%s</a>', \Podlove\t( 'Edit this show' ) );
		}
			
		wp_nonce_field( plugin_basename( __FILE__ ), 'podlove_noncename' );
		?>
		<input type="hidden" name="show-media-file-base-uri" value="<?php echo $show->media_file_base_uri; ?>" />
		<table class="form-table">
			<?php foreach ( $this->form_data as $key => $value ): ?>
				<?php \Podlove\Form\input( '_podlove_meta[' . $show->id . ']', $release->{$key}, $key, $value ); ?>
			<?php endforeach; ?>
			<?php \Podlove\Form\input( '_podlove_meta[' . $show->id . ']', $format_values, 'formats', $formats_form ); ?>
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

		if ( ! isset( $_POST[ '_podlove_meta' ] ) || ! is_array( $_POST[ '_podlove_meta' ] ) )
			return;
		
		// What do we need these loops for?
		// When you submit a checkbox, the value is "on" when active.
		// However, when unchecked, nothing is sent at all. So, to determine
		// the difference between "new" and "unchecked", we populate all unset
		// fields with false manually.
		$formats = array_map( function( $f ) { return $f->id; }, \Podlove\Model\MediaFormat::all() );
		foreach ( $_POST[ '_podlove_meta' ] as $show_id => $_ ) {
			foreach ( $this->form_data as $key => $value ) {
				if ( ! isset( $_POST[ '_podlove_meta' ][ $show_id ][ $key ] ) )
					$_POST[ '_podlove_meta' ][ $show_id ][ $key ] = false;
				elseif ( $_POST[ '_podlove_meta' ][ $show_id ][ $key ] === 'on' )
					$_POST[ '_podlove_meta' ][ $show_id ][ $key ] = true;
			}
			foreach ( $formats as $format_id ) {
				if ( ! isset( $_POST[ '_podlove_meta' ][ $show_id ][ 'formats' ][ $format_id ] ) ) {
					$_POST[ '_podlove_meta' ][ $show_id ][ 'formats' ][ $format_id ] = false;
				} elseif ( $_POST[ '_podlove_meta' ][ $show_id ][ 'formats' ][ $format_id ] === 'on' ) {
					$_POST[ '_podlove_meta' ][ $show_id ][ 'formats' ][ $format_id ] = true;
				}
			}
		}

		// save changes
		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );

		foreach ( $_POST[ '_podlove_meta' ] as $show_id => $release_values ) {
			$release = \Podlove\Model\Release::find_or_create_by_episode_id_and_show_id( $episode->id, $show_id );

			// save generic release fields
			foreach ( $this->form_data as $release_column => $_ )
				$release->{$release_column} = $release_values[ $release_column ];
			
			$release->save();

			// save files/formats
			foreach ( $release_values[ 'formats' ] as $format_id => $format_value ) {
				$file = \Podlove\Model\File::find_by_release_id_and_format_id( $release->id, $format_id );

				if ( $file === NULL && $format_value ) {
					// create file
					$file = new \Podlove\Model\File();
					$file->release_id = $release->id;
					$file->format_id = $format_id;
					$file->save();
				} elseif ( $file !== NULL && ! $format_value ) {
					// delete file
					$file->delete();
				}
			}

		}
	}
}