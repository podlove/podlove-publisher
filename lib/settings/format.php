<?php
namespace Podlove\Settings;

class Format {
	
	protected $field_keys;
	
	public function __construct( $handle ) {
		
		$this->field_keys = array(
			'name' => array(
				'label'       => \Podlove\t( 'Name' ),
				'description' => \Podlove\t( '' )
			),
			'type' => array(
				'label'       => \Podlove\t( 'Format Type' ),
				'description' => \Podlove\t( 'Example: audio' )
			),
			'mime_type' => array(
				'label'       => \Podlove\t( 'Format Mime Type' ),
				'description' => \Podlove\t( 'Example: audio/mpeg4' )
			),
			'extension' => array(
				'label'       => \Podlove\t( 'Format Extension' ),
				'description' => \Podlove\t( 'Example: m4a' )
			),
		);
		
		add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Formats',
			/* $menu_title */ 'Formats',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_formats_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}
	
	/**
	 * Process form: save/update a format
	 */
	private function save() {
		if ( ! isset( $_REQUEST[ 'format' ] ) )
			return;
			
		$format = \Podlove\Model\Format::find_by_id( $_REQUEST[ 'format' ] );
		
		if ( ! isset( $_POST[ 'podlove_format' ] ) || ! is_array( $_POST[ 'podlove_format' ] ) )
			return;
			
		foreach ( $this->field_keys as $key => $values ) {
			if ( isset( $values[ 'args' ] ) && isset( $values[ 'args' ][ 'type' ] ) && $values[ 'args' ][ 'type' ] == 'checkbox' ) {
				$format->{$key} = ( isset( $_POST[ 'podlove_format' ][ $key ] ) &&  $_POST[ 'podlove_format' ][ $key ] === 'on' ) ? 1 : 0;
			} else {
				$format->{$key} = $_POST[ 'podlove_format' ][ $key ];
			}
		}
		$format->save();
		
		$this->redirect( 'edit', $format->id );
	}
	
	/**
	 * Process form: create a format
	 */
	private function create() {
		global $wpdb;
		
		$format = new \Podlove\Model\Format;
		
		if ( ! isset( $_POST[ 'podlove_format' ] ) || ! is_array( $_POST[ 'podlove_format' ] ) )
			return;
			
		foreach ( $_POST[ 'podlove_format' ] as $key => $value ) {
			$format->{$key} = $value;
		}
		$format->save();

		$this->redirect( 'edit', $wpdb->insert_id );
	}
	
	/**
	 * Process form: delete a format
	 */
	private function delete() {
		if ( ! isset( $_REQUEST[ 'format' ] ) )
			return;

		\Podlove\Model\Format::find_by_id( $_REQUEST[ 'format' ] )->delete();
		
		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $format_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST[ 'page' ];
		$show   = ( $format_id ) ? '&format=' . $format_id : '';
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
			<h2>Podlove Formats <a href="?page=<?php echo $_REQUEST[ 'page' ]; ?>&amp;action=new" class="add-new-h2"><?php echo \Podlove\t( 'Add New' ); ?></a></h2>
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
		$format = new \Podlove\Model\Format;
		?>
		<h3><?php echo \Podlove\t( 'Add New Format' ); ?></h3>
		<?php
		$this->form_template( $format, 'create', \Podlove\t( 'Add New Format' ) );
	}
	
	private function view_template() {
		$table = new \Podlove\Format_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function form_template( $format, $action, $button_text = NULL ) {
		$field_keys = $this->field_keys;

		\Podlove\Form\build_for( $format, array( 'hidden' => array( 'format' => $format->id, 'action' => $action ) ), function ( $format ) use ( $field_keys ) {
			foreach ( $field_keys as $key => $value )
				\Podlove\Form\input( 'podlove_format', $format->{$key}, $key, $value );
		} );
	}
	
	private function edit_template() {
		$format = \Podlove\Model\Format::find_by_id( $_REQUEST[ 'format' ] );
		?>
		<h3>Edit Format: <?php echo $format->name ?></h3>
		
		<?php $this->form_template( $format, 'save' ); ?>
		<?php
	}
	
}