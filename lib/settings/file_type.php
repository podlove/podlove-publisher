<?php
namespace Podlove\Settings;

class FileType {
	
	use \Podlove\HasPageDocumentationTrait;
	
	public function __construct( $handle ) {
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}
	
	/**
	 * Process form: save/update a format
	 */
	private function save() {
		if ( ! isset( $_REQUEST['file_type'] ) )
			return;
			
		$format = \Podlove\Model\FileType::find_by_id( $_REQUEST['file_type'] );
		
		if ( ! isset( $_POST['podlove_file_type'] ) || ! is_array( $_POST['podlove_file_type'] ) )
			return;
			
		foreach ( $_POST['podlove_file_type'] as $key => $value ) {
			$value = trim( $value );
			$value = $key === 'extension' ? trim( $value, '.' ) : $value;
			$format->{$key} = $value;
		}
			
		$format->save();

		if (isset($_POST['submit_and_stay'])) {
			$this->redirect( 'edit', $format->id );
		} else {
			$this->redirect( 'index', $format->id );
		}
	}
	
	/**
	 * Process form: create a format
	 */
	private function create() {
		global $wpdb;
		
		$format = new \Podlove\Model\FileType;
		
		if ( ! isset( $_POST['podlove_file_type'] ) || ! is_array( $_POST['podlove_file_type'] ) )
			return;
			
		foreach ( $_POST['podlove_file_type'] as $key => $value ) {
			$format->{$key} = $value;
		}
		$format->save();

		if (isset($_POST['submit_and_stay'])) {
			$this->redirect( 'edit', $format->id );
		} else {
			$this->redirect( 'index' );
		}
	}
	
	/**
	 * Process form: delete a format
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['file_type'] ) )
			return;

		\Podlove\Model\FileType::find_by_id( $_REQUEST['file_type'] )->delete();
		
		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $format_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'] . '&podlove_tab=' . $_REQUEST['podlove_tab'];
		$show   = ( $format_id ) ? '&file_type=' . $format_id : '';
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
		</div>	
		<?php
	}
	
	private function new_template() {
		$format = new \Podlove\Model\FileType;
		?>
		<h3><?php echo __( 'Add New File Type', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $format, 'create', __( 'Add New Format', 'podlove' ) );
	}
	
	private function view_template() {
		?>
		<h2>
			<a href="?page=<?php echo $_REQUEST['page']; ?>&amp;podlove_tab=<?php echo $_REQUEST['podlove_tab'] ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a>
		</h2>
		<p>
			<?php echo __( 'This is a list of all file types the publisher knows about. If you would like to serve assets of an unknown file type, you must add it here before you can create the asset.', 'podlove' ); ?>
		</p>
		<?php
		$table = new \Podlove\File_Type_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function form_template( $format, $action, $button_text = NULL ) {

		$form_args = array(
			'context' => 'podlove_file_type',
			'hidden'  => array(
				'file_type'   => $format->id,
				'action'      => $action,
				'podlove_tab' => $_REQUEST['podlove_tab']
			),
			'submit_button' => false, // for custom control in form_end
			'form_end' => function() {
				echo "<p>";
				submit_button( __('Save Changes'), 'primary', 'submit', false );
				echo " ";
				submit_button( __('Save Changes and Continue Editing', 'podlove'), 'secondary', 'submit_and_stay', false );
				echo "</p>";
			}
		);

		\Podlove\Form\build_for( $format, $form_args, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

			$types = array();
			foreach ( \Podlove\Model\FileType::get_types() as $type ) {
				$types[ $type ] = $type;
			}

	 		$wrapper->string( 'name', array(
	 			'label'       => __( 'Name', 'podlove' ),
	 			'html' => array( 'class' => 'podlove-check-input' ),
	 			'description' => '' ) );

	 		$wrapper->select( 'type', array(
	 			'label'       => __( 'Document Type', 'podlove' ),
		 		'options'     => $types
	 		) );

	 		$wrapper->string( 'mime_type', array(
	 			'label'       => __( 'Format Mime Type', 'podlove' ),
	 			'html' => array( 'class' => 'podlove-check-input' ),
	 			'description' => __( 'Example: audio/mp4', 'podlove' ) ) );

	 		$wrapper->string( 'extension', array(
	 			'label'       => __( 'Format Extension', 'podlove' ),
	 			'html' => array( 'class' => 'podlove-check-input' ),
	 			'description' => __( 'Example: m4a', 'podlove' ) ) );
		} );
	}
	
	private function edit_template() {
		$format = \Podlove\Model\FileType::find_by_id( $_REQUEST['file_type'] );
		?>
		<h3><?php echo __( 'Edit File Type', 'podlove' ) ?>: <?php echo $format->name ?></h3>
		
		<?php $this->form_template( $format, 'save' ); ?>
		<?php
	}
	
}