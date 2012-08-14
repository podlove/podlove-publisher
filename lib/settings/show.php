<?php
namespace Podlove\Settings;

class Show {
	
	static $pagehook;
	
	public function __construct( $handle ) {
		
		Show::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Shows',
			/* $menu_title */ 'Shows',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_shows_settings_handle',
			/* $function   */ array( $this, 'page' )
		);

		// submenu entry for each show
		$shows = \Podlove\Model\Show::all();
		foreach ( $shows as $show ) {
			add_submenu_page(
				/* $parent_slug*/ $handle,
				/* $page_title */ '&nbsp;&nbsp;' . $show->name,
				/* $menu_title */ '&nbsp;&nbsp;' . $show->name,
				/* $capability */ 'administrator',
				/* $menu_slug  */ 'podlove_show_' . $show->id . '_handle',
				/* $function   */ function () use ( $show ) {
					// whatever works, right?
					?>
					<script type="text/javascript">
					window.location = "?page=podlove_shows_settings_handle&action=edit&show=<?php echo $show->id; ?>";
					</script>
					<?php
				}
			);
		}

		add_action( 'admin_init', array( $this, 'process_form' ) );

		add_action( 'load-' . Show::$pagehook, function () {
			
			wp_register_script(
				/* $handle */ 'jquery-validate',
				/* $src    */ \Podlove\PLUGIN_URL . '/js/jquery.validate.min.js',
				/* $deps   */ array( 'jquery' ),
				/* $ver    */ '1.9.0'
			);

			wp_enqueue_script( 'jquery-validate' );

			wp_enqueue_script( 'postbox' );

			add_screen_option( 'layout_columns', array(
				'max' => 1, 'default' => 1
			) );
		} );

		// init so we can process creation and deletion of feeds
		new \Podlove\Settings\Feed();
		new \Podlove\Settings\MediaLocation();
	}
	
	/**
	 * Process form: save/update a show
	 * 
	 * @todo we could pass 'podlove_show' as hidden context field. Then most
	 * of this save logic is exactly the same for most if not all forms.
	 */
	private function save() {
		if ( ! isset( $_REQUEST['show'] ) )
			return;
			
		$show = \Podlove\Model\Show::find_by_id( $_REQUEST['show'] );
		
		if ( ! isset( $_POST['podlove_show'] ) || ! is_array( $_POST['podlove_show'] ) )
			return;

		// save form data
		foreach ( $_POST['podlove_show'] as $key => $value ) {
			if ( $key !== 'podlove_feed' )
				$show->{$key} = $value;
		}

		if ( isset( $_POST['podlove_show'][ 'podlove_media_location' ] ) ) {
			foreach ( $_POST['podlove_show'][ 'podlove_media_location' ] as $media_location_id => $media_location_data ) {
				$media_location = \Podlove\Model\MediaLocation::find_by_id( $media_location_id );
				foreach ( $media_location_data as $key => $value ) {
					$media_location->{$key} = $value;
				}
				$media_location->save();
			}
		}

		if ( isset( $_POST['podlove_show'][ 'podlove_feed' ] ) ) {
			foreach ( $_POST['podlove_show'][ 'podlove_feed' ] as $feed_id => $feed_data ) {
				$feed = \Podlove\Model\Feed::find_by_id( $feed_id );
				foreach ( $feed_data as $key => $value ) {
					$feed->{$key} = $value;
				}

				// special treatment for nested checkboxes
				// FIXME: make this work without hardcoding the field names
				$checkbox_fields = array( 'discoverable', 'enable', 'show_description' );
				foreach ( $checkbox_fields as $checkbox_field ) {
					if ( isset( $_POST['podlove_show'][ 'podlove_feed' ][ $feed->id ][ $checkbox_field ] ) && $_POST['podlove_show'][ 'podlove_feed' ][ $feed->id ][ $checkbox_field ] === 'on' ) {
						$feed->{$checkbox_field} = 1;
					} else {
						$feed->{$checkbox_field} = 0;
					}
				}

				$feed->save();
			}
		}

		if ( isset( $_POST['podlove_webplayer_formats'] ) ) {
			$old_options = get_option( 'podlove_webplayer_formats', array() );
			$old_options[ $show->id ] = $_POST['podlove_webplayer_formats'];
			update_option( 'podlove_webplayer_formats', $old_options );
		}
			
		// special treatment for checkboxes
		// reason:	if you uncheck a checkbox and submit the form, there is no
		// 			data sent at all. That's why there is the extra hidden field
		// 			"checkboxes". We can iterate over it here and check if the
		// 			known checkboxes have been set or not.
		if ( isset( $_POST['checkboxes'] ) && is_array( $_POST['checkboxes'] ) ) {
			foreach ( $_POST['checkboxes'] as $checkbox_field_name ) {
				if ( isset( $_POST['podlove_show'][ $checkbox_field_name ] ) && $_POST['podlove_show'][ $checkbox_field_name ] === 'on' ) {
					$show->{$checkbox_field_name} = 1;
				} else {
					$show->{$checkbox_field_name} = 0;
				}
			}
		}

		$show->save();
		
		$this->redirect( 'edit', $show->id );
	}
	
	/**
	 * Process form: create new show
	 */
	private function create() {
		$show = new \Podlove\Model\Show;
		
		if ( ! isset( $_POST['podlove_show'] ) || ! is_array( $_POST['podlove_show'] ) )
			return;
			
		foreach ( $_POST['podlove_show'] as $key => $value ) {
			$show->{$key} = $value;
		}
		$show->save();
		
		// create media location stub
		$media_location = new \Podlove\Model\MediaLocation;
		$media_location->show_id = $show->id;
		$media_location->save();

		// create feed stub
		$feed = new \Podlove\Model\Feed;
		$feed->show_id = $show->id;
		$feed->media_location_id = $media_location->id;
		$feed->save();
		
		$this->redirect( 'edit', $show->id );
	}
	
	/**
	 * Process form: delete a show
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['show'] ) || isset( $_REQUEST['feed'] ) || isset( $_REQUEST['media_location'] ) )
			return;
			
		$show = \Podlove\Model\Show::find_by_id( $_REQUEST['show'] );
		$show->delete();

		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $show_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $show_id ) ? '&show=' . $show_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}
	
	public function process_form() {
		$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;
		
		if ( $action === 'save' ) {
			$this->save();
		} elseif ( $action === 'create' ) {
			$this->create();
		} elseif ( $action === 'delete' ) {
			$this->delete();
		}
	}
	
	public function page() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2>Podlove Shows <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
			<?php
			$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;
			switch ( $action ) {
				case 'new':
					$this->new_template();
					break;
				case 'edit':
					$this->edit_template();
					break;
				case 'index':
				default:
					$this->view_template();
					break;
			}
			?>

			<!-- Stuff for opening / closing metaboxes -->
			<script type="text/javascript">
			jQuery( document ).ready( function( $ ){
				// close postboxes that should be closed
				$( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
				// postboxes setup
				postboxes.add_postbox_toggles( '<?php echo Show::$pagehook; ?>' );
			} );
			</script>

			<!-- jQuery validate -->
			<script type="text/javascript">
			jQuery(document).ready(function($){
				$("#show_form").validate();
			});
			</script>

			<!-- css for form validation -->
			<style type="text/css">
			form label.error {
				padding-left: 10px;
				color: red;
			}

			form input.error {
				border: 1px dotted red;
			}
			</style>

			<form style='display: none' method='get' action=''>
				<?php
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
				?>
			</form>
		</div>	
		<?php
	}
	
	private function new_template() {
		$show = new \Podlove\Model\Show;
		?>
		<h3><?php echo __( 'Add New Show', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $show, 'create', __( 'Add New Show', 'podlove' ) );
	}
	
	private function view_template() {
		?>
		<style type="text/css">
			.wp-list-table.shows .column-id {
				width: 40px;
			}
			.wp-list-table.shows .column-cover {
				width: 75px;
			}
		</style>
		<?php
		$table = new \Podlove\Show_List_Table();
		$table->prepare_items();
		$table->display();
	}

	private function edit_template() {
		$show = \Podlove\Model\Show::find_by_id( $_REQUEST['show'] );
		?>
		<h3><?php echo __( 'Edit Show', 'podlove' ); ?>: <?php echo $show->name ?></h3>
		
		<?php $this->form_template( $show, 'save' ); ?>

		<!-- highlight show entry in menu -->
		<script type="text/javascript">
		jQuery(function($) {
			$(".wp-submenu a:contains('<?php echo $show->name; ?>')").css({fontWeight: 'bold'});
		});
		</script>

		<?php
	}
	
	private function form_template( $show, $action, $button_text = NULL ) {

		$form_attributes = array(
			'context'    => 'podlove_show',
			'attributes' => array( 'id' => 'show_form' ),
			'hidden'     => array( 'show' => $show->id, 'action' => $action )
		);

		\Podlove\Form\build_for( $show, $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$show = $form->object;

			$wrapper->string( 'name', array(
				'label'       => __( 'Show Title', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'html'        => array( 'class' => 'regular-text required' )
			) );

			$wrapper->string( 'subtitle', array(
				'label'       => __( 'Show Subtitle', 'podlove' ),
				'description' => __( 'The subtitle is used by iTunes.', 'podlove' ),
				'html'        => array( 'class' => 'regular-text' )
			) );

			$wrapper->text( 'summary', array(
				'label'       => __( 'Summary', 'podlove' ),
				'description' => __( 'A couple of sentences describing the show.', 'podlove' ),
				'html'        => array( 'rows' => 5, 'cols' => 40 )
			) );

			$wrapper->string( 'slug', array(
				'label'       => __( 'Show Slug', 'podlove' ),
				'description' => __( 'The abbreviation for your show. Commonly the initials of the title.', 'podlove' ),
				'html'        => array( 'class' => 'regular-text required' )
			) );

			$wrapper->image( 'cover_image', array(
				'label'        => __( 'Cover Art URL', 'podlove' ),
				'description'  => __( 'JPEG or PNG. At least 1400 x 1400 pixels.', 'podlove' ),
				'html'         => array( 'class' => 'regular-text' ),
				'image_width'  => 300,
				'image_height' => 300
			) );

			$wrapper->string( 'author_name', array(
				'label'       => __( 'Author Name', 'podlove' ),
				'description' => __( 'Publicly displayed in Podcast directories.', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );
	
			$wrapper->string( 'owner_name', array(
				'label'       => __( 'Owner Name', 'podlove' ),
				'description' => __( 'Used by iTunes and other Podcast directories to contact you.', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );
	
			$wrapper->string( 'owner_email', array(
				'label'       => __( 'Owner Email', 'podlove' ),
				'description' => __( 'Used by iTunes and other Podcast directories to contact you.', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );
	
			$wrapper->string( 'keywords', array(
				'label'       => __( 'Keywords', 'podlove' ),
				'description' => __( 'List of keywords. Separate with commas.', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );

			$wrapper->select( 'category_1', array(
				'label'       => __( 'iTunes Categories', 'podlove' ),
				'description' => '',
				'type'     => 'select',
				'options'  => \Podlove\Itunes\categories()
			) );

			$wrapper->select( 'category_2', array(
				'label'       => '',
				'description' => '',
				'type'     => 'select',
				'options'  => \Podlove\Itunes\categories()
			) );

			$wrapper->select( 'category_3', array(
				'label'       => '',
				'description' => '<br>'
				                 . __( 'For placement within the older, text-based browse system, podcast feeds may list up to 3 category/subcategory pairs. (For example, "Music" counts as 1, as does "Business > Careers.") For placement within the newer browse system based on Category links, however, and for placement within the Top Podcasts and Top Episodes lists that appear in the right column of most podcast pages, only the first category listed in the feed is used.' )
				                 . ' (<a href="http://www.apple.com/itunes/podcasts/specs.html#category" target="_blank">http://www.apple.com/itunes/podcasts/specs.html#category</a>)',
				'options'  => \Podlove\Itunes\categories()
			) );

			$wrapper->select( 'language', array(
				'label'       => __( 'Language', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'default'     => get_bloginfo( 'language' ),
				'options'  => \Podlove\Locale\locales()
			) );

			$wrapper->select( 'explicit', array(
				'label'       => __( 'Explicit Content?', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'type'    => 'checkbox',
                'options'  => array(0 => 'no', 1 => 'yes', 2 => 'clean')
			) );

			$wrapper->string( 'media_file_base_uri', array(
				'label'       => __( 'Media File Base URL', 'podlove' ),
				'description' => __( 'Example: http://cdn.example.com/pod/', 'podlove' ),
				'html' => array( 'class' => 'regular-text required' )
			) );

			$wrapper->checkbox( 'supports_cover_art', array(
				'label'       => __( 'Supports Cover Art', 'podlove' ),
				'description' => __( 'Lets you provide a URL to a cover image for each episode.', 'podlove' )
			) );

			$media_locations = \Podlove\Model\MediaLocation::find_all_by_show_id( $form->object->id );

			if ( $media_locations ) {
					add_meta_box(
						/* $id            */ 'podlove_webplayer_settings',
						/* $title         */ __( 'Configure Webplayer' ),
						/* $callback      */ '\Podlove\Settings\Show::podlove_webplayer_settings_callback',
						/* $page          */ \Podlove\Settings\Show::$pagehook,
						/* $context       */ 'webplayer',
						/* $priority      */ 'default',
						/* $callback_args */ array( $media_locations, $wrapper )
					);
				
					add_meta_box(
						/* $id            */ 'podlove_media_locations',
						/* $title         */ __( 'Configure Media Files' ),
						/* $callback      */ '\Podlove\Settings\Show::nested_feed_media_locations_callback',
						/* $page          */ \Podlove\Settings\Show::$pagehook,
						/* $context       */ 'media_locations',
						/* $priority      */ 'default',
						/* $callback_args */ array( $media_locations, $wrapper )
					);
			}

			$feeds = \Podlove\Model\Feed::find_all_by_show_id( $form->object->id );

			if ( $feeds ) {	
				foreach ( $feeds as $feed ) {
					add_meta_box(
						/* $id            */ 'podlove_feed_' . $feed->id,
						/* $title         */ sprintf( __( 'Configure Feed: %s' ), $feed->name ),
						/* $callback      */ '\Podlove\Settings\Show::nested_feed_meta_box_callback',
						/* $page          */ \Podlove\Settings\Show::$pagehook,
						/* $context       */ 'feeds',
						/* $priority      */ 'default',
						/* $callback_args */ array( $feed, $wrapper )
					);
				}
			}
			?>
			<tr>
				<td colspan="2" class="metabox-holder">
					<?php 
					do_meta_boxes( \Podlove\Settings\Show::$pagehook, 'media_locations', array() );
					?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php if ( $show->is_new() ): ?>
						<span><?php echo __( 'After you have saved the show, you can add media locations for it here.', 'podlove' ); ?></span>
					<?php else: ?>
					<span class="add">
						<a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=create&amp;subject=media_location&amp;show=<?php echo $show->id; ?>" style="float: left" class="button-primary add">
							<?php echo __( 'Add New Media File', 'podlove' ); ?>
						</a>
					</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="metabox-holder">
					<?php 
					do_meta_boxes( \Podlove\Settings\Show::$pagehook, 'webplayer', array() );
					?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="metabox-holder">
					<?php 
					do_meta_boxes( \Podlove\Settings\Show::$pagehook, 'feeds', array() );
					?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php if ( $show->is_new() ): ?>
						<span><?php echo __( 'After you have saved the show, you can add feeds for it here.', 'podlove' ); ?></span>
					<?php else: ?>
					<span class="add">
						<a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=create&amp;subject=feed&amp;show=<?php echo $show->id; ?>" style="float: left" class="button-primary add">
							<?php echo __( 'Add New Feed', 'podlove' ); ?>
						</a>
					</span>
					<?php endif; ?>
				</td>
			</tr>
			<?php

		} );
	}

	public static function podlove_webplayer_settings_callback( $post, $args ) {
		$media_locations = $args['args'][ 0 ];
		$show            = $media_locations[ 0 ]->show();
		$wrapper         = $args['args'][ 1 ];

		$formats = array(
			'audio' => array(
				'mp3' => __( 'MP3 Audio', 'podlove' ),
				'mp4' => __( 'MP4 Audio', 'podlove' ),
				'ogg' => __( 'OGG Audio', 'podlove' )
			),
			'video' => array(
				'mp4'  => __( 'MP4 Video', 'podlove' ),
				'ogg'  => __( 'OGG Video', 'podlove' ),
				'webm' => __( 'Webm Video', 'podlove' ),
			)
		);

		$all_formats_data = get_option( 'podlove_webplayer_formats' );
		if ( isset( $all_formats_data[ $show->id ] ) )
			$formats_data = $all_formats_data[ $show->id ];
		else
			$formats_data = array();

		?>
		<a name="webplayer_settings"></a>

		<?php echo __( 'Webplayers are able to provide various media formats depending on context. Try to provide as many as possible.', 'podlove' ); ?>

		<table style="width: 100%">
			<?php foreach ( $formats as $format => $extensions ): ?>
				<?php foreach ( $extensions as $extension_key => $extension_label ): ?>
					<?php
					$id    = sprintf( 'podlove_webplayer_formats_%s_%s'  , $format, $extension_key );
					$name  = sprintf( 'podlove_webplayer_formats[%s][%s]', $format, $extension_key );
					$value = ( isset( $formats_data[$format] ) && isset( $formats_data[$format][$extension_key] ) ) ? $formats_data[$format][$extension_key] : 0;
					?>
					<tr>
						<th scope="row" valign="top">
							<label for="<?php echo $id ?>"><?php echo $extension_label; ?></label>
						</th>
						<td>
							<div>
								<select name="<?php echo $name; ?>" id="<?php echo $id; ?>">
									<option value="0" <?php selected( 0, $value ); ?> ><?php echo __( 'Unused', 'podlove' ); ?></option>
									<?php foreach ($media_locations as $media_location): ?>
										<?php if ( $media_location->media_format() ): ?>
											<option value="<?php echo $media_location->id; ?>" <?php selected( $media_location->id, $value ); ?>><?php echo $media_location->title ?></option>
										<?php endif ?>
									<?php endforeach ?>
								</select>
							</div>
						</td>
					</tr>
				<?php endforeach ?>
			<?php endforeach ?>
		</table>
		<?php
	}

	public static function nested_feed_media_locations_callback( $post, $args ) {
		$media_locations = $args['args'][ 0 ];
		$wrapper         = $args['args'][ 1 ];

		$raw_formats = \Podlove\Model\MediaFormat::all();
		$formats = array();
		foreach ( $raw_formats as $format ) {
			$formats[ $format->id ] = array(
				'title'     => $format->title(),
				'extension' => $format->extension
			);
		}
		?>

		<style type="text/css">
		.media_file_wrapper {
			border: 1px solid #CCC;
			border-radius: 10px;
			margin: 8px 0px;
		}
		</style>

		<div id="media_format_data" class="hidden">
			<?php echo json_encode( $formats ); ?>	
		</div>

		<a name="media_locations"></a>
			<?php
			$format_optionlist = array_map( function ( $f ) {
				return $f['title'];
			}, $formats );

			foreach ( $media_locations as $media_location ) {
				?>
				<table style="width: 100%" class="media_file_wrapper">
				<?php
				$wrapper->fields_for( $media_location, array( 'context' => 'podlove_media_location' ), function ( $media_location_form ) use ( $format_optionlist ) {
					$f = new \Podlove\Form\Input\TableWrapper( $media_location_form );

					$media_location = $media_location_form->object;

					$f->select( 'media_format_id', array(
						'label'       => __( 'File Format', 'podlove' ),
						'description' => __( '', 'podlove' ),
						'options'     => $format_optionlist
					) );

					$f->string( 'title', array(
						'label'       => __( 'Title', 'podlove' ),
						'description' => __( 'Description to identify the media file type to the user in download buttons.', 'podlove' ),
						'html' => array( 'class' => 'regular-text required' )
					) );

					$f->string( 'suffix', array(
						'label'       => __( 'Suffix', 'podlove' ),
						'description' => __( 'Is appended to the media file name. Use if you have multiple media files with the same extension.', 'podlove' ),
						'html' => array( 'class' => 'regular-text' )
					) );

					$f->string( 'url_template', array(
						'label'       => __( 'URL Template', 'podlove' ),
						'description' => sprintf( __( 'Preview: %s' ), '<span class="url_template_preview"></span><br/>', 'podlove' ),
						'html' => array( 'class' => 'large-text required' )
					) );

				} );
				?>
				<tr>
					<td colspan="2">
						<span class="delete">
							<a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=delete&amp;subject=media_location&amp;show=<?php echo $media_location->show()->id; ?>&amp;media_location=<?php echo $media_location->id; ?>" style="float: right" class="button-secondary delete">
								<?php echo __( 'Remove Media File Configuration', 'podlove' ); ?>
							</a>
						</span>
					</td>
				</tr>
				</table>
				<?php
			}
			?>
		<?php
	}

	public static function nested_feed_meta_box_callback( $post, $args ) {
		$feed    = $args['args'][ 0 ];
		$wrapper = $args['args'][ 1 ];
		?>
		<a name="feed_<?php echo $feed->id; ?>"></a>
		<table>
			<?php
			$wrapper->fields_for( $feed, array( 'context' => 'podlove_feed' ), function ( $feed_form ) {
				$feed_wrapper = new \Podlove\Form\Input\TableWrapper( $feed_form );

				$feed = $feed_form->object;

				$media_locations = $feed->show()->media_locations();
				$locations = array();
				foreach ( $media_locations as $location ) {
					$locations[ $location->id ] = $location->title;
				}	

				$feed_wrapper->string( 'name', array(
					'label'       => __( 'Internal Name', 'podlove' ),
					'description' => __( 'This is how this feed is presented to you within WordPress.', 'podlove' ),
					'html' => array( 'class' => 'regular-text required' )
				) );

				$feed_wrapper->checkbox( 'discoverable', array(
					'label'       => __( 'Discoverable?', 'podlove' ),
					'description' => __( 'Embed a meta tag into the head of your site so browsers and feed readers will find the link to the feed.', 'podlove' )
				) );

				// This was used for feed discovery before, which is now generated.
				// TODO: Let the user choose: Default or Custom
				// $feed_wrapper->string( 'title', array(
				// 	'label'       => __( 'Feed Title', 'podlove' ),
				// 	'description' => __( 'This is how this feed is presented to users of podcast clients.', 'podlove' ),
				// 	'html' => array( 'class' => 'regular-text' )
				// ) );
				
				$feed_wrapper->string( 'slug', array(
					'label'       => __( 'Slug', 'podlove' ),
					'description' => ( $feed ) ? sprintf( __( 'Feed identifier. URL: %s', 'podlove' ), $feed->get_subscribe_url() ) : '',
					'html'        => array( 'class' => 'regular-text required' )
				) );

				$feed_wrapper->radio( 'format', array(
					'label'   => __( 'Format', 'podlove' ),
					'options' => array( 'atom' => 'Atom', 'rss' => 'RSS' )
				) );
							
				$feed_wrapper->select( 'media_location_id', array(
					'label'       => __( 'Media File', 'podlove' ),
					'description' => __( 'Choose the file location for this feed.', 'podlove' ),
					'options'     => $locations,
					'html'        => array( 'class' => 'required' )
				) );
				
				$feed_wrapper->string( 'itunes_feed_id', array(
					'label'       => __( 'iTunes Feed ID', 'podlove' ),
					'description' => __( 'Is used to generate a link to the iTunes directory.', 'podlove' ),
					'html'        => array( 'class' => 'regular-text' )
				) );
								
				// todo: select box with localized language names
				// todo: add PING url; see feedburner doc
				$feed_wrapper->string( 'redirect_url', array(
					'label'       => __( 'Redirect Url', 'podlove' ),
					'description' => __( 'e.g. Feedburner URL', 'podlove' ),
					'html' => array( 'class' => 'regular-text' )
				) );
				
				$feed_wrapper->checkbox( 'enable', array(
					'label'       => __( 'Allow Submission to Directories', 'podlove' ),
					'description' => __( 'Allow this feed to appear in podcast directories.', 'podlove' )
				) );
				
				$feed_wrapper->checkbox( 'show_description', array(
					'label'       => __( 'Include Description?', 'podlove' ),
					'description' => __( 'You may want to hide the episode descriptions to reduce the feed file size.', 'podlove' )
				) );
				
				// todo include summary?
				$feed_wrapper->string( 'limit_items', array(
					'label'       => __( 'Limit Items', 'podlove' ),
					'description' => __( 'A feed only displays the most recent episodes. Define the amount. Leave empty to use the WordPress default.', 'podlove' ),
					'html' => array( 'class' => 'regular-text' )
				) );
				
				// todo: radio 1) wp default (show default) 2) custom 3) all 4) limit feed size (default = 512k = feedburner)						
			} );
			?>
			<tr>
				<td colspan="2">
					<span class="delete">
						<a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=delete&amp;subject=feed&amp;show=<?php echo $feed->show()->id; ?>&amp;feed=<?php echo $feed->id; ?>" style="float: right" class="button-secondary delete">
							<?php echo __( 'Delete Feed', 'podlove' ); ?>
						</a>
					</span>
				</td>
			</tr>
		</table>
		<?php
	}
	
}
