<?php
namespace Podlove\Settings;

class Show {
	
	protected $pagehook;
	
	public function __construct( $handle ) {
		
		$this->pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Shows',
			/* $menu_title */ 'Shows',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_shows_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
		
		if ( isset( $_REQUEST[ 'show' ] ) ) {
			$show_id = (int) $_REQUEST[ 'show' ];
			$feeds = \Podlove\Model\Feed::find_all_by_show_id( $show_id );
			foreach ( $feeds as $feed ) {
				new \Podlove\Settings\Feed( $this->pagehook, $feed );
			}
			// init one feed so the hooks can be registered
			if ( count( $feeds ) === 0 )
				new \Podlove\Settings\Feed( $this->pagehook, NULL );
			
		}
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
		foreach ( $_POST[ 'podlove_show' ] as $key => $value )
			$show->{$key} = $value;

		// speacial treatment for checkboxes
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
		if ( ! isset( $_REQUEST[ 'show' ] ) || isset( $_REQUEST[ 'feed' ] ) )
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
	
	private function form_template( $show, $action, $button_text = NULL ) {
		\Podlove\Form\build_for( $show, array( 'context' => 'podlove_show', 'hidden' => array( 'show' => $show->id, 'action' => $action ) ), function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

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

			$wrapper->text( 'summary', array(
				'label'       => \Podlove\t( 'Summary' ),
				'description' => \Podlove\t( 'A couple of sentences describing the show.' ),
				'html'        => array( 'rows' => 5, 'cols' => 40 )
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

		} );
		?>
		
		<?php // todo: see WordPress settings page for menus. suitable for feed management? ?>
		<?php if ( ! $show->is_new() ): ?>
			<h3><?php echo \Podlove\t( 'Feeds' ); ?> <a href="?page=<?php echo $_REQUEST[ 'page' ]; ?>&amp;show=<?php echo $show->id ?>&amp;action=create" class="add-new-h2" style="font-weight:normal"><?php echo \Podlove\t( 'Add New' ); ?></a></h3>

			<div class="metabox-holder">
				<?php
				if ( is_multisite() && is_plugin_active_for_network( plugin_basename( PLUGIN_FILE ) ) )
					$options = get_site_option( $_REQUEST[ 'page' ] );
				else
					$options = get_option( $_REQUEST[ 'page' ] );

				do_meta_boxes( $this->pagehook, 'normal', $options );
				do_meta_boxes( $this->pagehook, 'additional', $options );
				?>
			</div>
		<?php endif; ?>
		<?php
		// todo clean up show/feed form
		// - "save updates" should update whole page
	}
	
	private function edit_template() {
		$show = \Podlove\Model\Show::find_by_id( $_REQUEST[ 'show' ] );
		?>
		<h3><?php echo \Podlove\t( 'Edit Show' ); ?>: <?php echo $show->name ?></h3>
		
		<?php $this->form_template( $show, 'save' ); ?>
		<?php
	}
	
}
