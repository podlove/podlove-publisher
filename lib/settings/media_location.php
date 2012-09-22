<?php 
namespace Podlove\Settings;
use \Podlove\Model;

class MediaLocation {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		self::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Media Locations',
			/* $menu_title */ 'Media Locations',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_media_locations_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}
	
	/**
	 * Process form: save/update a format
	 */
	private function save() {
		if ( ! isset( $_REQUEST['media_location'] ) )
			return;
			
		$media_location = \Podlove\Model\MediaLocation::find_by_id( $_REQUEST['media_location'] );
		$media_location->update_attributes( $_POST['podlove_media_location'] );
		
		$this->redirect( 'edit', $media_location->id );
	}
	
	/**
	 * Process form: create a format
	 */
	private function create() {
		global $wpdb;
		
		$media_location = new \Podlove\Model\MediaLocation;
		$media_location->update_attributes( $_POST['podlove_media_location'] );

		$this->redirect( 'edit', $wpdb->insert_id );
	}
	
	/**
	 * Process form: delete a format
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['media_location'] ) )
			return;

		\Podlove\Model\MediaLocation::find_by_id( $_REQUEST['media_location'] )->delete();
		
		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $media_location_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $media_location_id ) ? '&media_location=' . $media_location_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}
	
	public function process_form() {

		if ( ! isset( $_REQUEST['media_location'] ) )
			return;

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
			<h2><?php echo __( 'Media Locations', 'podlove' ); ?> <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
			<?php
			$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : NULL;
			switch ( $action ) {
				case 'new':   $this->new_template();  break;
				case 'edit':  $this->edit_template(); break;
				case 'index': $this->view_template(); break;
				default:      $this->view_template(); break;
			}
			?>
		</div>	
		<?php
	}
	
	private function new_template() {
		$media_location = new \Podlove\Model\MediaLocation;
		?>
		<h3><?php echo __( 'Add New media_location', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $media_location, 'create', __( 'Add New Media Location', 'podlove' ) );
	}
	
	private function view_template() {
		$table = new \Podlove\Media_Location_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function form_template( $media_location, $action, $button_text = NULL ) {

		$raw_formats = \Podlove\Model\MediaFormat::all();
		$formats = array();
		foreach ( $raw_formats as $format ) {
			$formats[ $format->id ] = array(
				'title'     => $format->title(),
				'extension' => $format->extension
			);
		}

		$format_optionlist = array_map( function ( $f ) {
			return $f['title'];
		}, $formats );

		$form_args = array(
			'context' => 'podlove_media_location',
			'hidden'  => array(
				'media_location' => $media_location->id,
				'action' => $action
			),
			'attributes' => array(
				'id' => 'podlove_media_locations'
			)
		);

		\Podlove\Form\build_for( $media_location, $form_args, function ( $form ) use ( $format_optionlist ) {
			$f = new \Podlove\Form\Input\TableWrapper( $form );

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

			$f->string( 'url_template', array(
				'label'       => __( 'URL Template', 'podlove' ),
				'description' => sprintf( __( 'Preview: %s' ), '<span class="url_template_preview"></span><br/>', 'podlove' ),
				'html' => array( 'class' => 'large-text required' ),
				'default' => '%media_file_base_url%%episode_slug%.%format_extension%'
			) );

			$f->checkbox( 'downloadable', array(
				'label'       => __( 'Downloadable', 'podlove' ),
				'description' => sprintf( 'Allow downloads for users.', 'podlove' ),
				'default' => true
			) );

		} );

		// hidden fields for JavaScript
		?>
		<input type="hidden" id="podlove_show_media_file_base_uri" value="<?php echo Model\Podcast::get_instance()->media_file_base_uri; ?>">
		<?php
	}
	
	private function edit_template() {
		$media_location = \Podlove\Model\MediaLocation::find_by_id( $_REQUEST['media_location'] );
		echo '<h3>' . sprintf( __( 'Edit Media Location: %s', 'podlove' ), $media_location->title ) . '</h3>';
		$this->form_template( $media_location, 'save' );
	}

}