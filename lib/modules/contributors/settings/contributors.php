<?php
namespace Podlove\Modules\Contributors\Settings;

use Podlove\Model;
use Podlove\Modules\Contributors\Model\ContributorRole;
use Podlove\Modules\Contributors\Model\ContributorGroup;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Contributors\Contributor_List_Table;

use \Podlove\Settings\Expert\Tabs;
use \Podlove\Modules\Contributors\Settings\Tab;

class Contributors {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Contributors::$pagehook = add_submenu_page(
			/* $parent_slug*/ 'edit.php?post_type=podcast',
			/* $page_title */ 'Contributors',
			/* $menu_title */ 'Contributors',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_contributors_settings_handle',
			/* $function   */ array( $this, 'page' )
		);

		$pagehook = self::$pagehook;

		add_action( 'admin_init', array( $this, 'process_form' ) );
		add_action( 'admin_print_styles', array( $this, 'scripts_and_styles' ) );
		add_action( "load-$pagehook",  array( $this, 'add_contributors_screen_options' ) );
		add_filter( 'admin_title', array( $this, 'add_contributor_to_title' ), 10, 2 );
	}

	public function add_contributor_to_title( $title ) {

		if ( ! isset( $_REQUEST['contributor'] ) )
			return $title;

		$contributor = Contributor::find_by_id( $_REQUEST['contributor'] );

		if ( ! is_object( $contributor ) )
			return $title;

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'edit' )
			return $title;

		return str_replace('Contributor', $contributor->getName() . ' &lsaquo; Contributor', $title);
	}
	
	public static function get_action_link( $contributor, $title, $action = 'edit', $class = 'link' ) {
		return sprintf(
			'<a href="?post_type=podcast&amp;page=%s&amp;action=%s&amp;contributor=%s" class="%s">' . $title . '</a>',
			$_REQUEST['page'],
			$action,
			$contributor->id,
			$class
		);
	}
	
	public function process_form() {
		if ( ! isset( $_REQUEST['contributor'] ) )
			return;

		$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;

		set_transient( 'podlove_needs_to_flush_rewrite_rules', true );
		
		if ( $action === 'save' ) {
			$this->save();
		} elseif ( $action === 'create' ) {
			$this->create();
		} elseif ( $action === 'delete' ) {
			$this->delete();
		}
	}

	public function page() {
		if ( isset($_GET["action"]) AND $_GET["action"] == 'confirm_delete' AND isset( $_REQUEST['contributor'] ) ) {
			 $contributor = Contributor::find_by_id( $_REQUEST['contributor'] );
			?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf( __( 'You selected to delete the contributor "%s". Please confirm this action.', 'podlove' ), $contributor->getName() ) ?>
					</strong>
				</p>
				<p>
					<?php echo self::get_action_link( $contributor, __( 'Delete contributor permanently', 'podlove' ), 'delete', 'button' ) ?>
					<?php echo self::get_action_link( $contributor, __( 'Don\'t change anything', 'podlove' ), 'keep', 'button-primary' ) ?>
				</p>
			</div>
			<?php
		}
		
		?>

		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
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
		if ( ! isset( $_REQUEST['contributor'] ) )
			return;
			
		$contributor = Contributor::find_by_id( $_REQUEST['contributor'] );

		foreach ($_POST['podlove_contributor'] as $contributor_attribute => $contributor_value) {
			if ( isset($contributor->$contributor_attribute) )
				$contributor->$contributor_attribute = $contributor_value;
		}

		$contributor->update_attributes( $_POST['podlove_contributor'] );

		do_action( 'update_podlove_contributor', $contributor );
		
		if (isset($_POST['submit_and_stay'])) {
			$this->redirect( 'edit', $contributor->id );
		} else {
			$this->redirect( 'index', $contributor->id );
		}
	}
	
	/**
	 * Process form: create a contributor
	 */
	private function create() {
		global $wpdb;
		
		$contributor = new Contributor;
		$contributor->update_attributes( $_POST['podlove_contributor'] );

		do_action( 'update_podlove_contributor', $contributor );

		if (isset($_POST['submit_and_stay'])) {
			$this->redirect( 'edit', $contributor->id );
		} else {
			$this->redirect( 'index' );
		}
	}
	
	/**
	 * Process form: delete a contributor
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['contributor'] ) )
			return;

		Contributor::find_by_id( $_REQUEST['contributor'] )->delete();
		
		$this->redirect( 'index' );
	}

	public function add_contributors_screen_options() {

		add_screen_option( 'per_page', array(
	       'label'   => 'Contributors',
	       'default' => 10,
	       'option'  => 'podlove_contributors_per_page'
		) );

		$this->table = new Contributor_List_Table();
	}

	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $contributor_id = NULL ) {
		$page   = 'edit.php?post_type=podcast&page=' . $_REQUEST['page'];
		$show   = ( $contributor_id ) ? '&contributor=' . $contributor_id : '';
		$action = '&action=' . $action;
		$tab 	= '&podlove_tab=' . $_REQUEST['podlove_tab'];
		
		wp_redirect( admin_url( $page . $show . $action . $tab ) );
		exit;
	}
	
	private function view_template() {
		?><h2><?php echo __( 'Contributors', 'podlove' ); ?> <a href="?post_type=podcast&amp;page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2><?php
		$this->table->prepare_items();
		echo \Podlove\Flattr\getFlattrScript();
		$this->table->display();
	}

	private function tab_interface($heading) {
		$tabs = new Tabs($heading);
		$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Contributors\General( __( 'General', 'podlove' ), true ) );
		$tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Contributors\Affiliation( __( 'Affiliation', 'podlove' ) ) );
		$this->tabs = apply_filters( 'podlove_contributor_settings_tabs', $tabs );
		$this->tabs->initCurrentTab();

		echo $this->tabs->getTabsHTML();
		echo $this->tabs->getCurrentTabPage();
	}
	
	private function new_template() {
		$contributor = new Contributor;

		$this->tab_interface(__('New Contributor', 'podlove'));
	}
	
	private function edit_template() {
		$contributor = Contributor::find_by_id( $_REQUEST['contributor'] );

		$this->tab_interface(sprintf( __( 'Contributor: %s', 'podlove' ), $contributor->getName()));
	}

	public function scripts_and_styles() {
		wp_register_script( 'podlove-contributors-admin-script', \Podlove\PLUGIN_URL . '/lib/modules/contributors/js/admin.js', array( 'jquery-ui-autocomplete' ) );
		wp_enqueue_script( 'podlove-contributors-admin-script' );
	}
}
