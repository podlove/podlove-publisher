<?php
namespace Podlove\Settings;

class Show {
	
	static $pagehook;
	
	public function __construct( $handle ) {
		
		self::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Shows',
			/* $menu_title */ 'Shows',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_shows_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );

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
		if ( ! isset( $_REQUEST[ 'show' ] ) )
			return;
			
		$show = \Podlove\Model\Show::find_by_id( $_REQUEST[ 'show' ] );
		
		if ( ! isset( $_POST[ 'podlove_show' ] ) || ! is_array( $_POST[ 'podlove_show' ] ) )
			return;

		// save form data
		foreach ( $_POST[ 'podlove_show' ] as $key => $value ) {
			if ( $key !== 'podlove_feed' )
				$show->{$key} = $value;
		}

		if ( isset( $_POST[ 'podlove_show' ][ 'podlove_media_location' ] ) ) {
			foreach ( $_POST[ 'podlove_show' ][ 'podlove_media_location' ] as $media_location_id => $media_location_data ) {
				$media_location = \Podlove\Model\MediaLocation::find_by_id( $media_location_id );
				foreach ( $media_location_data as $key => $value ) {
					$media_location->{$key} = $value;
				}
				$media_location->save();
			}
		}

		if ( isset( $_POST[ 'podlove_show' ][ 'podlove_feed' ] ) ) {
			foreach ( $_POST[ 'podlove_show' ][ 'podlove_feed' ] as $feed_id => $feed_data ) {
				$feed = \Podlove\Model\Feed::find_by_id( $feed_id );
				foreach ( $feed_data as $key => $value ) {
					$feed->{$key} = $value;
				}

				// special treatment for nested checkboxes
				// FIXME: make this work without hardcoding the field names
				$checkbox_fields = array( 'discoverable', 'block', 'show_description' );
				foreach ( $checkbox_fields as $checkbox_field ) {
					if ( isset( $_POST[ 'podlove_show' ][ 'podlove_feed' ][ $feed->id ][ $checkbox_field ] ) && $_POST[ 'podlove_show' ][ 'podlove_feed' ][ $feed->id ][ $checkbox_field ] === 'on' ) {
						$feed->{$checkbox_field} = 1;
					} else {
						$feed->{$checkbox_field} = 0;
					}
				}

				$feed->save();
			}
		}
			
		// special treatment for checkboxes
		// reason:	if you uncheck a checkbox and submit the form, there is no
		// 			data sent at all. That's why there is the extra hidden field
		// 			"checkboxes". We can iterate over it here and check if the
		// 			known checkboxes have been set or not.
		if ( isset( $_POST[ 'checkboxes' ] ) && is_array( $_POST[ 'checkboxes' ] ) ) {
			foreach ( $_POST[ 'checkboxes' ] as $checkbox_field_name ) {
				if ( isset( $_POST[ 'podlove_show' ][ $checkbox_field_name ] ) && $_POST[ 'podlove_show' ][ $checkbox_field_name ] === 'on' ) {
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
		
		if ( ! isset( $_POST[ 'podlove_show' ] ) || ! is_array( $_POST[ 'podlove_show' ] ) )
			return;
			
		foreach ( $_POST[ 'podlove_show' ] as $key => $value ) {
			$show->{$key} = $value;
		}
		$show->save();
		
		// create feed stub
		$feed = new \Podlove\Model\Feed;
		$feed->show_id = $show->id;
		$feed->discoverable = 1;
		$feed->show_description = 1;
		$feed->itunes_block = 0;
		$feed->save();
		
		$this->redirect( 'edit', $show->id );
	}
	
	/**
	 * Process form: delete a show
	 */
	private function delete() {
		if ( ! isset( $_REQUEST[ 'show' ] ) || isset( $_REQUEST[ 'feed' ] ) || isset( $_REQUEST[ 'media_location' ] ) )
			return;
			
		$show = \Podlove\Model\Show::find_by_id( $_REQUEST[ 'show' ] );
		$show->delete();

		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $show_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST[ 'page' ];
		$show   = ( $show_id ) ? '&show=' . $show_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}
	
	public function process_form() {
		$action = ( isset( $_REQUEST[ 'action' ] ) ) ? $_REQUEST[ 'action' ] : NULL;
		
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
			<h2>Podlove Shows <a href="?page=<?php echo $_REQUEST[ 'page' ]; ?>&amp;action=new" class="add-new-h2"><?php echo \Podlove\t( 'Add New' ); ?></a></h2>
			<?php
			$action = ( isset( $_REQUEST[ 'action' ] ) ) ? $_REQUEST[ 'action' ] : NULL;
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
		</div>	
		<?php
	}
	
	private function new_template() {
		$show = new \Podlove\Model\Show;
		?>
		<h3><?php echo \Podlove\t( 'Add New Show' ); ?></h3>
		<?php
		$this->form_template( $show, 'create', \Podlove\t( 'Add New Show' ) );
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
		$show = \Podlove\Model\Show::find_by_id( $_REQUEST[ 'show' ] );
		?>
		<h3><?php echo \Podlove\t( 'Edit Show' ); ?>: <?php echo $show->name ?></h3>
		
		<?php $this->form_template( $show, 'save' ); ?>
		<?php
	}
	
	private function form_template( $show, $action, $button_text = NULL ) {
		\Podlove\Form\build_for( $show, array( 'context' => 'podlove_show', 'hidden' => array( 'show' => $show->id, 'action' => $action ) ), function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$show = $form->object;

			$wrapper->string( 'name', array(
				'label'       => \Podlove\t( 'Show Title' ),
				'description' => \Podlove\t( '' ),
				'html'        => array( 'class' => 'regular-text' )
			) );

			$wrapper->string( 'subtitle', array(
				'label'       => \Podlove\t( 'Show Subtitle' ),
				'description' => \Podlove\t( 'The subtitle is used by iTunes.' ),
				'html'        => array( 'class' => 'regular-text' )
			) );

			$wrapper->text( 'summary', array(
				'label'       => \Podlove\t( 'Summary' ),
				'description' => \Podlove\t( 'A couple of sentences describing the show.' ),
				'html'        => array( 'rows' => 5, 'cols' => 40 )
			) );

			$wrapper->string( 'slug', array(
				'label'       => \Podlove\t( 'Show Slug' ),
				'description' => \Podlove\t( 'Is part of the feed URL.' ),
				'html'        => array( 'class' => 'regular-text' )
			) );

			$wrapper->image( 'cover_image', array(
				'label'        => \Podlove\t( 'Cover Image' ),
				'description'  => \Podlove\t( 'Cover Image URL, 600x600px recommended.' ),
				'html'         => array( 'class' => 'regular-text' ),
				'image_width'  => 300,
				'image_height' => 300
			) );

			$wrapper->string( 'author_name', array(
				'label'       => \Podlove\t( 'Author Name' ),
				'description' => \Podlove\t( 'Publicly displayed in Podcast directories.' ),
				'html' => array( 'class' => 'regular-text' )
			) );
	
			$wrapper->string( 'owner_name', array(
				'label'       => \Podlove\t( 'Owner Name' ),
				'description' => \Podlove\t( 'Used by iTunes and other Podcast directories to contact you.' ),
				'html' => array( 'class' => 'regular-text' )
			) );
	
			$wrapper->string( 'owner_email', array(
				'label'       => \Podlove\t( 'Owner Email' ),
				'description' => \Podlove\t( 'Used by iTunes and other Podcast directories to contact you.' ),
				'html' => array( 'class' => 'regular-text' )
			) );
	
			$wrapper->string( 'keywords', array(
				'label'       => \Podlove\t( 'Keywords' ),
				'description' => \Podlove\t( 'List of keywords. Separate with commas.' ),
				'html' => array( 'class' => 'regular-text' )
			) );

			$wrapper->select( 'category_1', array(
				'label'       => \Podlove\t( 'iTunes Categories' ),
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
				'description' => '',
				'type'     => 'select',
				'options'  => \Podlove\Itunes\categories()
			) );

			$wrapper->checkbox( 'explicit', array(
				'label'       => \Podlove\t( 'Explicit Content?' ),
				'description' => \Podlove\t( '' ),
				'type'    => 'checkbox'
			) );

			$wrapper->string( 'media_file_base_uri', array(
				'label'       => \Podlove\t( 'Media File Base URI' ),
				'description' => \Podlove\t( 'Example: http://cdn.example.com/pod/' ),
				'html' => array( 'class' => 'regular-text' )
			) );

			?>
			<tr>
				<td colspan="2">
					<?php if ( $show->is_new() ): ?>
						<span><?php echo \Podlove\t( 'After you have saved the show, you can add media locations for it here.' ); ?></span>
					<?php else: ?>
					<span class="add">
						<a href="?page=<?php echo $_REQUEST[ 'page' ]; ?>&amp;action=create&amp;subject=media_location&amp;show=<?php echo $show->id; ?>" style="float: left" class="button-primary add">
							<?php echo \Podlove\t( 'Add New Media Location' ); ?>
						</a>
					</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php if ( $show->is_new() ): ?>
						<span><?php echo \Podlove\t( 'After you have saved the show, you can add feeds for it here.' ); ?></span>
					<?php else: ?>
					<span class="add">
						<a href="?page=<?php echo $_REQUEST[ 'page' ]; ?>&amp;action=create&amp;subject=feed&amp;show=<?php echo $show->id; ?>" style="float: left" class="button-primary add">
							<?php echo \Podlove\t( 'Add New Feed' ); ?>
						</a>
					</span>
					<?php endif; ?>
				</td>
			</tr>
			<?php 
			$media_locations = \Podlove\Model\MediaLocation::find_all_by_show_id( $form->object->id );

			if ( $media_locations ) {
					add_meta_box(
						/* $id            */ 'podlove_media_locations',
						/* $title         */ __( 'Configure Media Locations' ),
						/* $callback      */ '\Podlove\Settings\Show::nested_feed_media_locations_callback',
						/* $page          */ \Podlove\Settings\Show::$pagehook,
						/* $context       */ 'normal',
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
						/* $context       */ 'normal',
						/* $priority      */ 'default',
						/* $callback_args */ array( $feed, $wrapper )
					);
				}
			}
			?>
			<tr>
				<td colspan="2" class="metabox-holder">
					<?php 
					do_meta_boxes( \Podlove\Settings\Show::$pagehook, 'normal', array() );
					do_meta_boxes( \Podlove\Settings\Show::$pagehook, 'additional', array() );
					?>
				</td>
			</tr>
			<?php

		} );
	}

	public static function nested_feed_media_locations_callback( $post, $args ) {
		$media_locations = $args[ 'args' ][ 0 ];
		$wrapper         = $args[ 'args' ][ 1 ];
		?>
		<a name="media_locations"></a>
		<table style="width: 100%">
			<?php
			$raw_formats = \Podlove\Model\MediaFormat::all();
			$formats = array();
			foreach ( $raw_formats as $format ) {
				$formats[ $format->id ] = $format->name . ' (' . $format->extension . ')';
			}

			foreach ( $media_locations as $media_location ) {
				$wrapper->fields_for( $media_location, array( 'context' => 'podlove_media_location' ), function ( $media_location_form ) use ( $formats ) {
					$f = new \Podlove\Form\Input\TableWrapper( $media_location_form );

					$media_location = $media_location_form->object;

					$f->select( 'media_format_id', array(
						'label'       => \Podlove\t( 'File Format' ),
						'description' => \Podlove\t( '' ),
						'options'     => $formats
					) );

					$f->string( 'suffix', array(
						'label'       => \Podlove\t( 'Suffix' ),
						'description' => \Podlove\t( 'Is appended to the media file name.' ),
						'html' => array( 'class' => 'regular-text' )
					) );

					$f->string( 'url_template', array(
						'label'       => \Podlove\t( 'URL Template' ),
						'description' => sprintf( \Podlove\t( 'Read %sdocumentation%s for help.' ), '<a href="#" target="_blank">', '</a>' ),
						'html' => array( 'class' => 'large-text' )
					) );

				} );
				?>
				<tr>
					<td colspan="2">
						<span class="delete">
							<a href="?page=<?php echo $_REQUEST[ 'page' ]; ?>&amp;action=delete&amp;subject=media_location&amp;show=<?php echo $media_location->show()->id; ?>&amp;media_location=<?php echo $media_location->id; ?>" style="float: right" class="button-secondary delete">
								<?php echo \Podlove\t( 'Delete Media Location' ); ?>
							</a>
						</span>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php
	}

	public static function nested_feed_meta_box_callback( $post, $args ) {
		$feed    = $args[ 'args' ][ 0 ];
		$wrapper = $args[ 'args' ][ 1 ];
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
					$locations[ $location->id ] = $location->title();
				}	

				$feed_wrapper->string( 'name', array(
					'label'       => \Podlove\t( 'Internal Name' ),
					'description' => \Podlove\t( 'This is how this feed is presented to you within WordPress.' ),
					'html' => array( 'class' => 'regular-text' )
				) );

				$feed_wrapper->checkbox( 'discoverable', array(
					'label'       => \Podlove\t( 'Discoverable?' ),
					'description' => \Podlove\t( 'Embed a meta tag into the head of your site so browsers and feed readers will find the link to the feed.' )
				) );

				$feed_wrapper->string( 'title', array(
					'label'       => \Podlove\t( 'Feed Title' ),
					'description' => \Podlove\t( 'This is how this feed is presented to users of podcast clients.' ),
					'html' => array( 'class' => 'regular-text' )
				) );
				
				$feed_wrapper->string( 'slug', array(
					'label'       => \Podlove\t( 'Slug' ),
					'description' => ( $feed ) ? sprintf( \Podlove\t( 'Feed URL: %s' ), $feed->get_subscribe_url() ) : '',
					'html' => array( 'class' => 'regular-text' )
				) );
							
				$feed_wrapper->select( 'media_location_id', array(
					'label'       => \Podlove\t( 'Media Location' ),
					'description' => \Podlove\t( 'Choose the file location for this feed.' ),
					'options'     => $locations
				) );
				
				$feed_wrapper->string( 'itunes_feed_id', array(
					'label'       => \Podlove\t( 'iTunes Feed ID' ),
					'description' => \Podlove\t( 'Is used to generate a link to the iTunes directory.' ),
					'html' => array( 'class' => 'regular-text' )
				) );
				
				$feed_wrapper->string( 'language', array(
					'label'       => \Podlove\t( 'Language' ),
					'description' => \Podlove\t( '' ),
					'default'     => get_bloginfo( 'language' ),
					'html' => array( 'class' => 'regular-text' )
				) );
				
				// todo: select box with localized language names
				// todo: add PING url; see feedburner doc
				$feed_wrapper->string( 'redirect_url', array(
					'label'       => \Podlove\t( 'Redirect Url' ),
					'description' => \Podlove\t( 'e.g. Feedburner URL' ),
					'html' => array( 'class' => 'regular-text' )
				) );
				
				$feed_wrapper->checkbox( 'block', array(
					'label'       => \Podlove\t( 'Block feed?' ),
					'description' => \Podlove\t( 'Forbid podcast directories (e.g. iTunes) to list this feed.' )
				) );
				
				$feed_wrapper->checkbox( 'show_description', array(
					'label'       => \Podlove\t( 'Include Description?' ),
					'description' => \Podlove\t( 'You may want to hide the episode descriptions to reduce the feed file size.' )
				) );
				
				// todo include summary?
				$feed_wrapper->string( 'limit_items', array(
					'label'       => \Podlove\t( 'Limit Items' ),
					'description' => \Podlove\t( 'A feed only displays the most recent episodes. Define the amount. Leave empty to use the WordPress default.' ),
					'html' => array( 'class' => 'regular-text' )
				) );
				
				// todo: radio 1) wp default (show default) 2) custom 3) all 4) limit feed size (default = 512k = feedburner)						
			} );
			?>
			<tr>
				<td colspan="2">
					<span class="delete">
						<a href="?page=<?php echo $_REQUEST[ 'page' ]; ?>&amp;action=delete&amp;subject=feed&amp;show=<?php echo $feed->show()->id; ?>&amp;feed=<?php echo $feed->id; ?>" style="float: right" class="button-secondary delete">
							<?php echo \Podlove\t( 'Delete Feed' ); ?>
						</a>
					</span>
				</td>
			</tr>
		</table>
		<?php
	}
	
}
