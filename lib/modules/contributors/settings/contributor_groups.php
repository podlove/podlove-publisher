<?php
namespace Podlove\Modules\Contributors\Settings;

use Podlove\Model;

class ContributorGroups {

	static $pagehook;
	
	public function __construct( $handle ) {
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public static function get_action_link( $group, $title, $action = 'edit', $class = 'link' ) {
		$request = ( isset( $_REQUEST['podlove_tab'] ) ? "&amp;podlove_tab=".$_REQUEST['podlove_tab'] : '' );
		return sprintf(
			'<a href="?page=%s%s&amp;action=%s&amp;group=%s" class="%s">' . $title . '</a>',
			$_REQUEST['page'],
			$request,
			$action,
			$group->id,
			$class
		);
	}

	public static function process_form() {

		if ( ! isset( $_REQUEST['group'] ) )
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
		if ( isset($_GET["action"]) AND $_GET["action"] == 'confirm_delete' AND isset( $_REQUEST['group'] ) ) {
			 $group = \Podlove\Modules\Contributors\Model\ContributorGroup::find_by_id( $_REQUEST['group'] );
			?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf( __( 'You selected to delete the group "%s". Please confirm this action.', 'podlove' ), $group->title ) ?>
					</strong>
				</p>
				<p>
					<?php echo self::get_action_link( $group, __( 'Delete group permanently', 'podlove' ), 'delete', 'button' ) ?>
					<?php echo self::get_action_link( $group, __( 'Don\'t change anything', 'podlove' ), 'keep', 'button-primary' ) ?>
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
		if ( ! isset( $_REQUEST['group'] ) )
			return;
			
		$group = \Podlove\Modules\Contributors\Model\ContributorGroup::find_by_id( $_REQUEST['group'] );
		$group->update_attributes( $_POST['podlove_contributor_group'] );
		
		self::redirect( 'index', $group->id );
	}
	
	/**
	 * Process form: create a contributor
	 */
	private function create() {
		global $wpdb;
		
		$contributor = new \Podlove\Modules\Contributors\Model\ContributorGroup;
		$contributor->update_attributes( $_POST['podlove_contributor_group'] );

		self::redirect( 'index' );
	}
	
	/**
	 * Process form: delete a contributor
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['group'] ) )
			return;

		\Podlove\Modules\Contributors\Model\ContributorGroup::find_by_id( $_REQUEST['group'] )->delete();
		
		self::redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $group_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $group_id ) ? '&group=' . $group_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}
	
	private function view_template() {
		?>
		<h2>
			<a href="?page=<?php echo $_REQUEST['page']; ?>&amp;podlove_tab=groups&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a>
		</h2>
		<?php
		echo __('Use groups to divide contributors by type of participation. Create a group for teams working together or for a supporting community. Team members can be displayed separately by using <a href=\'http://docs.podlove.org/publisher/shortcodes/#contributors\' target=\'_blank\'>appropriate option</a> to select a group.');
		$table = new \Podlove\Modules\Contributors\Contributor_Group_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function new_template() {
		$group = new \Podlove\Modules\Contributors\Model\ContributorGroup;
		?>
		<h3><?php echo __( 'Add New group', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $group, 'create', __( 'Add New group', 'podlove' ) );
	}
	
	private function edit_template() {
		$group = \Podlove\Modules\Contributors\Model\ContributorGroup::find_by_id( $_REQUEST['group'] );
		echo '<h3>' . sprintf( __( 'Edit group: %s', 'podlove' ), $group->title ) . '</h3>';
		$this->form_template( $group, 'save' );
	}
	
	private function form_template( $group, $action, $button_text = NULL ) {

		$form_args = array(
			'context' => 'podlove_contributor_group',
			'hidden'  => array(
				'group' => $group->id,
				'action' => $action
			)
		);

		\Podlove\Form\build_for( $group, $form_args, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

			$group = $form->object;

			$wrapper->string( 'title', array(
				'label'       => __( 'Group Title', 'podlove' ),
				'html'        => array( 'class' => 'required' )
			) );

			$wrapper->string( 'slug', array(
				'label'       => __( 'Group Slug', 'podlove' ),
				'html'        => array( 'class' => 'required' )
			) );

		} );
	}

}
