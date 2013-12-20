<?php
namespace Podlove\Modules\Contributors\Settings;

use Podlove\Model;

class ContributorRoles {

	static $pagehook;
	
	public function __construct( $handle ) {
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}
	
	public static function get_action_link( $role, $title, $action = 'edit', $class = 'link' ) {
		$request = ( isset( $_REQUEST['podlove_tab'] ) ? "&amp;podlove_tab=".$_REQUEST['podlove_tab'] : '' );
		return sprintf(
			'<a href="?page=%s%s&amp;action=%s&amp;role=%s" class="%s">' . $title . '</a>',
			$_REQUEST['page'],
			$request,
			$action,
			$role->id,
			$class
		);
	}
	
	public static function process_form() {

		if ( ! isset( $_REQUEST['role'] ) )
			return;

		$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;

		if ( $action === 'save' ) {
			self::save();
		} elseif ( $action === 'create' ) {
			self::create();
		} elseif ( $action === 'delete' ) {
			self::delete();
		}
	}

	public function page() {
		if ( isset($_GET["action"]) AND $_GET["action"] == 'confirm_delete' AND isset( $_REQUEST['role'] ) ) {
			 $role = \Podlove\Modules\Contributors\Model\ContributorRole::find_by_id( $_REQUEST['role'] );
			?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf( __( 'You selected to delete the role "%s". Please confirm this action.', 'podlove' ), $role->title ) ?>
					</strong>
				</p>
				<p>
					<?php echo self::get_action_link( $role, __( 'Delete role permanently', 'podlove' ), 'delete', 'button' ) ?>
					<?php echo self::get_action_link( $role, __( 'Don\'t change anything', 'podlove' ), 'keep', 'button-primary' ) ?>
				</p>
			</div>
			<?php
		}
		
		?>
		<div class="wrap">
			<?php
				if(isset($_GET["action"])) {
					switch ( $_GET["action"] ) {
						case 'new':   $this->new_template();  break;
						case 'edit':  $this->edit_template(); break;
						default:      $this->view_template(); break;
					}
				} else {
					$this->view_template();
				}
			?>
		</div>	
		<?php
	}
	
	/**
	 * Process form: save/update a contributor
	 */
	private function save() {
		if ( ! isset( $_REQUEST['role'] ) )
			return;
			
		$role = \Podlove\Modules\Contributors\Model\ContributorRole::find_by_id( $_REQUEST['role'] );
		$role->update_attributes( $_POST['podlove_contributor_role'] );
		
		self::redirect( 'index', $role->id );
	}
	
	/**
	 * Process form: create a contributor
	 */
	private function create() {
		global $wpdb;
		
		$contributor = new \Podlove\Modules\Contributors\Model\ContributorRole;
		$contributor->update_attributes( $_POST['podlove_contributor_role'] );

		self::redirect( 'index' );
	}
	
	/**
	 * Process form: delete a contributor
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['role'] ) )
			return;

		\Podlove\Modules\Contributors\Model\ContributorRole::find_by_id( $_REQUEST['role'] )->delete();
		
		self::redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $role_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $role_id ) ? '&role=' . $role_id : '';
		$action = '&action=' . $action;
		$tab = '&podlove_tab=roles';
		
		wp_redirect( admin_url( $page . $show . $action . $tab ) );
		exit;
	}
	
	private function view_template() {
		?>
		<h2>
			<a href="?page=<?php echo $_REQUEST['page']; ?>&amp;podlove_tab=roles&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a>
		</h2>
		<?php
		$table = new \Podlove\Modules\Contributors\Contributor_Role_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function new_template() {
		$role = new \Podlove\Modules\Contributors\Model\ContributorRole;
		?>
		<h3><?php echo __( 'Add New role', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $role, 'create', __( 'Add New Role', 'podlove' ) );
	}
	
	private function edit_template() {
		$role = \Podlove\Modules\Contributors\Model\ContributorRole::find_by_id( $_REQUEST['role'] );
		echo '<h3>' . sprintf( __( 'Edit Role: %s', 'podlove' ), $role->title ) . '</h3>';
		$this->form_template( $role, 'save' );
	}
	
	private function form_template( $role, $action, $button_text = NULL ) {

		$form_args = array(
			'context' => 'podlove_contributor_role',
			'hidden'  => array(
				'role' => $role->id,
				'action' => $action
			)
		);

		\Podlove\Form\build_for( $role, $form_args, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

			$role = $form->object;

			$wrapper->string( 'title', array(
				'label'       => __( 'Role Title', 'podlove' ),
				'html'        => array( 'class' => 'required' )
			) );

			$wrapper->string( 'slug', array(
				'label'       => __( 'Role Slug', 'podlove' ),
				'html'        => array( 'class' => 'required' )
			) );

		} );
	}
}
