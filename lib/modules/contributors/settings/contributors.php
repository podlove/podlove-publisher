<?php
namespace Podlove\Modules\Contributors\Settings;

use Podlove\Model;
use Podlove\Modules\Contributors\Model\ContributorRole;
use Podlove\Modules\Contributors\Model\ContributorGroup;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Contributors\Contributor_List_Table;

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
			<h2><?php echo __( 'Contributors', 'podlove' ); ?> <a href="?post_type=podcast&amp;page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
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
		$contributor->update_attributes( $_POST['podlove_contributor'] );

		do_action( 'update_podlove_contributor', $contributor );
		
		$this->redirect( 'index', $contributor->id );
	}
	
	/**
	 * Process form: create a contributor
	 */
	private function create() {
		global $wpdb;
		
		$contributor = new Contributor;
		$contributor->update_attributes( $_POST['podlove_contributor'] );

		do_action( 'update_podlove_contributor', $contributor );

		$this->redirect( 'index' );
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
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}
	
	private function view_template() {
		$this->table->prepare_items();
		echo \Podlove\Flattr\getFlattrScript();
		$this->table->display();
	}
	
	private function new_template() {
		$contributor = new Contributor;
		?>
		<h3><?php echo __( 'Add New Contributor', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $contributor, 'create', __( 'Add New Contributor', 'podlove' ) );
	}
	
	private function edit_template() {
		$contributor = Contributor::find_by_id( $_REQUEST['contributor'] );
		echo '<h3>' . sprintf( __( 'Edit Contributor: %s', 'podlove' ), $contributor->getName() ) . '</h3>';
		$this->form_template( $contributor, 'save' );
	}
	
	private function form_template( $contributor, $action, $button_text = NULL ) {

		$form_args = array(
			'context' => 'podlove_contributor',
			'hidden'  => array(
				'contributor' => $contributor->id,
				'action' => $action
			)
		);

		\Podlove\Form\build_for( $contributor, $form_args, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

			$contributor = $form->object;

			do_action( 'podlove_contributors_form_beginning', $wrapper );

			$wrapper->subheader( __( 'General', 'podlove' ) );

			$wrapper->string( 'publicname', array(
				'label'       => __( 'Public name', 'podlove' ),
				'description' => 'The Public Name will be used for public mentions. E.g. the Web Player. If left blank, it defaults to the "real name".',
				'html'        => array( 'class' => 'podlove-contributor-field' )
			) );

			$wrapper->string( 'realname', array(
				'label'       => __( 'Real name', 'podlove' ),
				'html'        => array( 'class' => 'required podlove-contributor-field' )
			) );

			$wrapper->string( 'nickname', array(
				'label'       => __( 'Nickname', 'podlove' ),
				'html'        => array( 'class' => 'podlove-contributor-field' )
			) );

			$wrapper->select( 'gender', array(
				'label'       => __( 'Gender', 'podlove' ),
				'options'     => array( 'female' => 'Female', 'male' => 'Male', 'none' => 'Not attributed')
			) );
			
			$wrapper->avatar( 'avatar', array(
				'label'       => __( 'Avatar', 'podlove' ),
				'description' => 'Either a Gravatar email adress or a URL.',
				'html'        => array( 'class' => 'podlove-contributor-field' )
			) );

			$wrapper->string( 'slug', array(
				'label'       => __( 'ID', 'podlove' ),
				'description' => 'The ID will be used as in internal identifier for e.g. shortcodes.',
				'html'        => array( 'class' => 'required podlove-contributor-field' )
			) );

			$wrapper->string( 'guid', array(
				'label'       => __( 'URI', 'podlove' ),
				'description' => __('An URI acts as a globally unique ID to identify contributors across podcasts on the internet.', 'podlove'),
				'html'        => array( 'class' => 'podlove-contributor-field' )
			) );		

			$wrapper->radio( 'visibility', array(
				'label'       => __( 'Visibility', 'podlove' ),
				'options'	  => array( '1' => 'Yes, the contributor’s information will be visible for the public (e.g. displayed in the Contributor Table).<br />', 
					                    '0' => 'No, the contributor’s information will be private and not visible for anybody.' ),
				'default'	  => '1'
			) );

			$wrapper->subheader( __( 'Affiliation', 'podlove' ) );
			
			$wrapper->string( 'organisation', array(
				'label'       => __( 'Organisation', 'podlove' ),
				'html'        => array( 'class' => 'podlove-contributor-field' )
			) );
			
			$wrapper->string( 'department', array(
				'label'       => __( 'Department', 'podlove' ),
				'html'        => array( 'class' => 'podlove-contributor-field' )
			) );

			$wrapper->string( 'jobtitle', array(
				'label'       => __( 'Job Title', 'podlove' ),
				'html'        => array( 'class' => 'podlove-contributor-field' )
			) );
			
			$wrapper->subheader( __( 'Contact', 'podlove' ) );
			
			$wrapper->string( 'privateemail', array(
				'label'       => __( 'Contact email', 'podlove' ),
				'description' => 'The provided email will be used for internal purposes only.',
				'html'        => array( 'class' => 'podlove-contributor-field' )
			) );
			
			$wrapper->string( 'publicemail', array(
				'label'       => __( 'Public email', 'podlove' ),
				'description' => 'This email will be displayed for public purposes.',
				'html'        => array( 'class' => 'podlove-contributor-field' )
			) );

			$wrapper->subheader( __( 'Flattr', 'podlove' ) );
			
			$wrapper->string( 'flattr', array(
				'label'       => __( 'Flattr Account', 'podlove' ),
				'description' => 'The provided flattr account will be used to generate episode specific Flattr buttons.',
				'html'        => array( 'class' => 'podlove-contributor-field' )
			) );

			do_action( 'podlove_contributors_form_end', $wrapper );

		} );
	}

	public function scripts_and_styles() {
		wp_register_script( 'podlove-contributors-admin-script', \Podlove\PLUGIN_URL . '/lib/modules/contributors/js/admin.js', array( 'jquery-ui-autocomplete' ) );
		wp_enqueue_script( 'podlove-contributors-admin-script' );
	}
}
