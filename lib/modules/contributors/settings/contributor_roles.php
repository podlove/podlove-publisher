<?php
namespace Podlove\Modules\Contributors\Settings;

use Podlove\Model;
use Podlove\Modules\Contributors\ContributorRole;

class ContributorRoles {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		ContributorRoles::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Contributor Roles',
			/* $menu_title */ 'Contributor Roles',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_contributor_roles',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
		add_action( 'admin_print_styles', array( $this, 'scripts_and_styles' ) );
	}
	
	public static function get_action_link( $role, $title, $action = 'edit', $class = 'link' ) {
		return sprintf(
			'<a href="?page=%s&action=%s&role=%s" class="%s">' . $title . '</a>',
			$_REQUEST['page'],
			$action,
			$role->id,
			$class
		);
	}
	
	public function process_form() {

		if ( ! isset( $_REQUEST['role'] ) )
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
		if ( isset($_GET["action"]) AND $_GET["action"] == 'confirm_delete' AND isset( $_REQUEST['role'] ) ) {
			 $role = \Podlove\Modules\Contributors\ContributorRole::find_by_id( $_REQUEST['role'] );
			?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf( __( 'You selected to delete the role "%s". Please confirm this action.', 'podlove' ), $role->realname ) ?>
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
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Contributor Roles', 'podlove' ); ?> <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
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
			
		$role = \Podlove\Modules\Contributors\ContributorRole::find_by_id( $_REQUEST['role'] );
		$role->update_attributes( $_POST['podlove_contributor_role'] );
		
		$this->redirect( 'index', $role->id );
	}
	
	/**
	 * Process form: create a contributor
	 */
	private function create() {
		global $wpdb;
		
		$contributor = new \Podlove\Modules\Contributors\ContributorRole;
		$contributor->update_attributes( $_POST['podlove_contributor_role'] );

		$this->redirect( 'index' );
	}
	
	/**
	 * Process form: delete a contributor
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['role'] ) )
			return;

		\Podlove\Modules\Contributors\ContributorRole::find_by_id( $_REQUEST['role'] )->delete();
		
		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $role_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $role_id ) ? '&role=' . $role_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}
	
	private function view_template() {
		$table = new \Podlove\Modules\Contributors\Contributor_Role_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function new_template() {
		$role = new \Podlove\Modules\Contributors\ContributorRole;
		?>
		<h3><?php echo __( 'Add New role', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $role, 'create', __( 'Add New Role', 'podlove' ) );
	}
	
	private function edit_template() {
		$role = \Podlove\Modules\Contributors\ContributorRole::find_by_id( $_REQUEST['role'] );
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
	
	public function scripts_and_styles() {
		// wp_register_script( 'podlove-contributors-admin-script', get_bloginfo('url') . '/wp-content/plugins/podlove-publisher/lib/modules/contributors/js/admin.js', array( 'jquery-ui-autocomplete' ) );
		// wp_enqueue_script( 'podlove-contributors-admin-script' );

		// wp_register_style( 'podlove-contributors-admin-style', get_bloginfo('url') . '/wp-content/plugins/podlove-publisher/lib/modules/contributors/css/admin.css' );
		// wp_enqueue_style( 'podlove-contributors-admin-style' );
	}
}
