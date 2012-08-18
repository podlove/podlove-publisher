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
				'label'       => __( 'Post Episode to Show', 'podlove' ), // todo: hide/show rest of the form
				'description' => '',
				'type'     => 'checkbox',
				'default'  => true
			),
			// todo: add subtitle; but as extra metabox
			'slug' => array(
				'label'       => __( 'Episode Media File Slug', 'podlove' ),
				'description' => '',
				'html'        => array( 'class' => 'regular-text' )
			),
			'duration' => array(
				'label'       => __( 'Duration', 'podlove' ),
				'description' => '',
				'html'        => array( 'class' => 'regular-text' )
			),
			'cover_art' => array(
				'label'       => __( 'Episode Cover Art URL', 'podlove' ),
				'description' => __( 'JPEG or PNG. At least 1400 x 1400 pixels.', 'podlove' ),
				'html'        => array( 'class' => 'regular-text' )
			),
			'chapters' => array(
				'label'       => __( 'Chapter Marks', 'podlove' ),
				'description' => __( 'One timepoint (hh:mm:ss[.mmm]) and the chapter title per line.', 'podlove' ),
				'type'        => 'text',
				'html'        => array(
					'class'       => 'large-text code',
					'placeholder' => '00:00:00.000 Intro'
				)
			),
			'enable' => array(
				'label'       => __( 'Enable?', 'podlove' ),
				'description' => __( 'Allow this episode to appear in podcast directories.', 'podlove' ),
				'type'        => 'checkbox',
				'default'     => true
			),
		);
		
		$labels = array(
			'name'               => __( 'Episodes', 'podlove' ),
			'singular_name'      => __( 'Episode', 'podlove' ),
			'add_new'            => __( 'Add New', 'podlove' ),
			'add_new_item'       => __( 'Add New Episode', 'podlove' ),
			'edit_item'          => __( 'Edit Episode', 'podlove' ),
			'new_item'           => __( 'New Episode', 'podlove' ),
			'all_items'          => __( 'All Episodes', 'podlove' ),
			'view_item'          => __( 'View Episode', 'podlove' ),
			'search_items'       => __( 'Search Episodes', 'podlove' ),
			'not_found'          => __( 'No episodes found', 'podlove' ),
			'not_found_in_trash' => __( 'No episodes found in Trash', 'podlove' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Episodes', 'podlove' ),
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
				'podlove_admin_episode',
				\Podlove\PLUGIN_URL . '/js/admin/episode.js',
				array( 'jquery' ),
				'1.1' 
			);

			wp_register_script(
				'podlove_admin_dashboard_validation',
				\Podlove\PLUGIN_URL . '/js/admin/dashboard_validation.js',
				array( 'jquery' ),
				'1.1' 
			);

			wp_register_script(
				'podlove_admin_media_location_settings',
				\Podlove\PLUGIN_URL . '/js/admin/media_location_settings.js',
				array( 'jquery' ),
				'1.1' 
			);

			wp_register_script(
				'podlove_admin',
				\Podlove\PLUGIN_URL . '/js/admin.js',
				array(
					'jquery',
					'podlove_admin_episode',
					'podlove_admin_dashboard_validation',
					'podlove_admin_media_location_settings'
				),
				'1.0' 
			);

			wp_enqueue_script( 'podlove_admin' );


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
		$dashboard_page_hook = add_submenu_page(
			/* $parent_slug*/ self::SETTINGS_PAGE_HANDLE,
			/* $page_title */ __( 'Dashboard', 'podlove' ),
			/* $menu_title */ __( 'Dashboard', 'podlove' ),
			/* $capability */ 'administrator',
			/* $menu_slug  */ self::SETTINGS_PAGE_HANDLE,
			/* $function   */ array( $this, 'settings_page' )
		);

		add_action( $dashboard_page_hook, function () {
			wp_enqueue_script( 'postbox' );
			add_screen_option( 'layout_columns', array(
				'max' => 2, 'default' => 2
			) );
		} );

		new \Podlove\Settings\Settings( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Podcast( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Format( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Feed( self::SETTINGS_PAGE_HANDLE );
		// new \Podlove\Settings\Show( self::SETTINGS_PAGE_HANDLE );
	}
	
	public function about_meta() {
		?>
		Podlove rocks 😸
		<?php
	}

	public function settings_page() {
		add_meta_box( self::SETTINGS_PAGE_HANDLE . '_about', __( 'About', 'podlove' ), array( $this, 'about_meta' ), self::SETTINGS_PAGE_HANDLE, 'side' );		
		add_meta_box( self::SETTINGS_PAGE_HANDLE . '_validation', __( 'Validate Podcast Files', 'podlove' ), array( $this, 'validate_podcast_files' ), self::SETTINGS_PAGE_HANDLE, 'normal' );

		do_action( 'podlove_dashboard_meta_boxes' );

		?>
		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>
			<h2><?php echo __( 'Podlove Dashboard', 'podlove' ); ?></h2>

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
						<?php do_meta_boxes( self::SETTINGS_PAGE_HANDLE, 'normal', NULL ); ?>
						<?php do_meta_boxes( self::SETTINGS_PAGE_HANDLE, 'additional', NULL ); ?>
						<?php do_action( 'podlove_settings_after_main_boxes' ); ?>						
					</div>
				</div>

				<br class="clear"/>

			</div>

			<!-- Stuff for opening / closing metaboxes -->
			<script type="text/javascript">
			jQuery( document ).ready( function( $ ){
				// close postboxes that should be closed
				$( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
				// postboxes setup
				postboxes.add_postbox_toggles( '<?php echo Podcast_Post_Type::SETTINGS_PAGE_HANDLE; ?>' );
			} );
			</script>

			<form style='display: none' method='get' action=''>
				<?php
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
				?>
			</form>

		</div>
		<?php
	}

	function validate_podcast_files() {
		$shows = \Podlove\Model\Show::all();

		if ( ! in_array( 'curl', get_loaded_extensions() ) ) {
			?>
			<div class="error"><p><strong>ERROR: </strong>You need curl for Podlove to run properly.</p></div>
			<?php
		}

		?>
		<div id="validation">

			<a href="#" id="validate_everything">
				<?php echo __( 'Validate Everything', 'podlove' ); ?>
			</a>

			<?php foreach ( $shows as $show ): ?>
				<?php
				echo "<h4>" . $show->name . "</h4>";
				$releases = $show->releases();
				?>

				<?php foreach ( $releases as $release ): ?>
					<?php
					$episode = $release->episode();
					$post_id = $episode->post_id;
					?>
					<div class="release">
						<div class="slug">
							<strong><?php echo sprintf( "%s (%s)", get_the_title( $post_id ), $release->slug ); ?></strong>
						</div>
						<div class="duration">
							<?php echo sprintf( __( 'Duration: %s', 'podlove' ), ( $release->duration ) ? $release->duration : __( '<span class="warning">empty</span>', 'podlove' ) ); ?>
						</div>
						<div class="chapters">
							<?php echo sprintf( __( 'Chapters: %s' ), strlen( $release->chapters ) > 0 ? __( 'existing', 'podlove' ) : __( '<span class="warning">empty</span>', 'podlove' ) ); ?>
						</div>
						<?php if ( $show->supports_cover_art ): ?>
							<div class="coverart">
								<?php echo sprintf( __( 'Cover Art: %s' ), strlen( $release->cover_art ) > 0 ? __( 'existing', 'podlove' ) : __( '<span class="warning">empty</span>', 'podlove' ) ); ?>
							</div>
						<?php endif; ?>
						<div class="media_files">
							<?php $media_files = $release->media_files(); ?>
							<?php foreach ( $media_files as $media_file ): ?>
								<div class="file" data-id="<?php echo $media_file->id; ?>">
									<span class="status">
										<?php if ( $media_file->size <= 0 ): ?>
											<?php echo __( "<span class=\"error\">filesize missing</span>", 'podlove' ); ?>
										<?php endif ?>
									</span>
									<span class="title"><?php echo $media_file->media_location()->title() ?></span>
									<span class="url">
										<?php echo $media_file->get_file_url(); ?>
									</span>
								</div>
							<?php endforeach ?>
						</div>
					</div>
				<?php endforeach ?>
			<?php endforeach ?>			
		</div>

		<style type="text/css">
		#validation h4 {
			font-size: 20px;
		}

		#validation .release {
			margin: 0 0 15px 0;
		}

		#validation .slug {
			font-size: 18px;
			margin: 0 0 5px 0;
		}

		#validation .warning {
			color: maroon;
		}

		#validation .error {
			color: red;
		}
		</style>
		<?php
	}
	
	/**
	 * Add Custom Post Type to all WordPress Feeds.
	 * 
	 * @param array $query_var
	 * @return array
	 */
	function add_post_type_to_feeds( $query_var ) {
		if ( isset( $query_var['feed'] ) ) {

			$extend = array(
				'post' => 'post',
				'podcast' => 'podcast'
			);

			if ( empty( $query_var['post_type'] ) ) {
				$query_var['post_type'] = $extend;
			} else {
				$query_var['post_type'] = array_merge( $query_var['post_type'], $extend );
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
				/* $title         */ __( 'Show: ', 'podlove' ) . $show->name,
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
		$show = $args['args'][ 0 ];
		$post_id = $post->ID;

		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );
		$release = \Podlove\Model\Release::find_or_create_by_episode_id_and_show_id( $episode->id, $show->id );

		// read / generate format data
		$media_locations = $show->valid_media_locations();

		$location_values = array();
		$location_options = array();
		foreach ( $media_locations as $location ) {
			// get formats configured for this show
			$location_options[ $location->id ] = $location->media_format()->name;
			// find out which formats are active
			$location_values[ $location->id ] = NULL !== \Podlove\Model\MediaFile::find_by_release_id_and_media_location_id( $release->id, $location->id );
		}

		$media_locations_form = array(
			'label'       => __( 'Media Files', 'podlove' ),
			'description' => '',
			'type'    => 'multiselect',
			'options' => $location_options,
			'default' => true,
			'multi_values' => $location_values,
			'multiselect_callback' => function ( $location_id ) use ( $release, $show ) {
				$location = \Podlove\Model\MediaLocation::find_by_id( $location_id );
				$format   = $location->media_format();
				$file     = \Podlove\Model\MediaFile::find_by_release_id_and_media_location_id( $release->id, $location->id );
				$filesize = ( is_object( $file ) ) ? $file->size : 0;					
				return 'data-template="' . $location->url_template . '" data-extension="' . $format->extension . '" data-suffix="' . $location->suffix . '" data-size="' . $filesize . '"';
			}
		);

		if ( empty( $location_options ) ) {
			$media_locations_form['description'] = sprintf( '<span style="color: red">%s</span>', __( 'You need to configure feeds for this show. No feeds, no fun.', 'podlove' ) )
			                                     . ' '
			                                     . sprintf( '<a href="' . admin_url( 'admin.php?page=podlove_shows_settings_handle&action=edit&show=' . $show->id ) . '">%s</a>', __( 'Edit this show', 'podlove' ) );
		}
			
		wp_nonce_field( \Podlove\PLUGIN_FILE, 'podlove_noncename' );
		?>
		<input type="hidden" name="show-media-file-base-uri" value="<?php echo $show->media_file_base_uri; ?>" />
		<table class="form-table">
			<?php 
			$form_data = $this->form_data;

			$form_data['media_locations'] = $media_locations_form;

			if ( ! $show->supports_cover_art )
				unset( $form_data['cover_art'] );

			\Podlove\Form\build_for( $release, array( 'context' => '_podlove_meta[' . $show->id . '][' . $release->id . ']', 'submit_button' => false, 'form' => false ), function ( $form ) use ( $form_data ) {
				$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
				$release = $form->object;

				foreach ( $form_data as $key => $value ) {

					// adjust chapter textfield height to its content
					// TODO: move into form toolkit
					if ( $key === 'chapters' ) {
						$rows = count( explode( "\n", $release->chapters ) );
						if ( $rows < 2 ) {
							$rows = 2;
						}
						$value['html']['rows'] = $rows;
					}

					$input_type = isset( $value['type'] ) ? $value['type'] : 'string';
					$wrapper->$input_type( $key, $value );
				}

			} );
			?>
		</table>
		<?php
	}

	public function save_postdata( $post_id ) {
		global $wpdb;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;
		
		if ( empty( $_POST['podlove_noncename'] ) || ! wp_verify_nonce( $_POST['podlove_noncename'], \Podlove\PLUGIN_FILE ) )
			return;
		
		// Check permissions
		if ( 'podcast' == $_POST['post_type'] ) {
		  if ( ! current_user_can( 'edit_post', $post_id ) )
			return;
		} else {
			return;
		}

		if ( ! isset( $_POST['_podlove_meta'] ) || ! is_array( $_POST['_podlove_meta'] ) )
			return;

		// What do we need these loops for?
		// When you submit a checkbox, the value is "on" when active.
		// However, when unchecked, nothing is sent at all. So, to determine
		// the difference between "new" and "unchecked", we populate all unset
		// fields with false manually.
		$media_locations = array_map( function( $f ) { return $f->id; }, \Podlove\Model\MediaFormat::all() );
		foreach ( $_POST['_podlove_meta'] as $show_id => $show_data ) {

			if ( ! isset( $_POST['_podlove_meta'][ $show_id ] ) ) {
				$_POST['_podlove_meta'][ $show_id ] = array();
			}

			foreach ( $show_data as $release_id => $_ ) {

				if ( ! isset( $_POST['_podlove_meta'][ $show_id ][ $release_id ] ) ) {
					$_POST['_podlove_meta'][ $show_id ][ $release_id ] = array();
				}

				if ( ! isset( $_POST['_podlove_meta'][ $show_id ][ $release_id ][ 'media_locations' ] ) ) {
					$_POST['_podlove_meta'][ $show_id ][ $release_id ][ 'media_locations' ] = array();
				}

				foreach ( $media_locations as $media_location_id ) {
					if ( ! isset( $_POST['_podlove_meta'][ $show_id ][ $release_id ][ 'media_locations' ][ $media_location_id ] ) ) {
						$_POST['_podlove_meta'][ $show_id ][ $release_id ][ 'media_locations' ][ $media_location_id ] = false;
					} elseif ( $_POST['_podlove_meta'][ $show_id ][ $release_id ][ 'media_locations' ][ $media_location_id ] === 'on' ) {
						$_POST['_podlove_meta'][ $show_id ][ $release_id ][ 'media_locations' ][ $media_location_id ] = true;
					}
				}
			}
		}

		// save changes
		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );

		foreach ( $_POST['_podlove_meta'] as $show_id => $show_values ) {
			foreach ( $show_values as $release_id => $release_values ) {
				$show    = \Podlove\Model\Show::find_by_id( $show_id );
				$release = \Podlove\Model\Release::find_by_id( $release_id );

				// save generic release fields
				foreach ( $this->form_data as $release_column => $_ ) {
					if ( isset( $release_values[ $release_column ] ) ) {
						$release->{$release_column} = $release_values[ $release_column ];
					}
				}

				if ( isset( $_POST['checkboxes'] ) && is_array( $_POST['checkboxes'] ) ) {
					foreach ( $_POST['checkboxes'] as $checkbox_field_name ) {
						if ( isset( $_POST['_podlove_meta'][ $show_id ][ $release->id ][ $checkbox_field_name ] ) && $_POST['_podlove_meta'][ $show_id ][ $release->id ][ $checkbox_field_name ] === 'on' ) {
							$release->{$checkbox_field_name} = 1;
						} else {
							$release->{$checkbox_field_name} = 0;
						}
					}
				}

				$release->save();

				// copy chapter info into custom meta for webplayer compatibility
				update_post_meta( $post_id, sprintf( '_podlove_chapters_%s', $show->slug ), $release->chapters );

				// save files/formats
				foreach ( $release_values['media_locations'] as $media_location_id => $media_location_value ) {
					$file = \Podlove\Model\MediaFile::find_by_release_id_and_media_location_id( $release->id, $media_location_id );

					if ( $file === NULL && $media_location_value ) {
						// create file
						$file = new \Podlove\Model\MediaFile();
						$file->release_id = $release->id;
						$file->media_location_id = $media_location_id;
						$file->save();
					} elseif ( $file !== NULL && ! $media_location_value ) {
						// delete file
						$file->delete();
					}
				}
			}
		}
	}
}

