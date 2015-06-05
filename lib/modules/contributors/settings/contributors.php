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
		$this->table->display();
	}

	private function contributor_form($heading) {

		$general_fields = [
			'realname' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'Real name', 'podlove' ),
					'html'        => array( 'class' => 'podlove-check-input required podlove-contributor-field' )
				)
			], 
			'publicname' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'Public name', 'podlove' ),
					'description' => 'The Public Name will be used for public mentions. E.g. the Web Player. If left blank, it defaults to the "real name".',
					'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				)
			], 
			'nickname' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'Nickname', 'podlove' ),
					'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				)
			],
			'gender' => [
				'field_type' => 'select',
				'field_options' => array(
					'label'       => __( 'Gender', 'podlove' ),
					'options'     => array( 'female' => 'Female', 'male' => 'Male', 'none' => 'Not attributed')
				)
			], 
			'privateemail' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'Contact email', 'podlove' ),
					'description' => 'The provided email will be used for internal purposes only.',
					'html'        => array( 'class' => 'podlove-contributor-field podlove-check-input', 'data-podlove-input-type' => 'email' )
				)
			],
			'avatar' => [
				'field_type' => 'avatar',
				'field_options' => array(
					'label'       => __( 'Avatar', 'podlove' ),
					'description' => 'Either a Gravatar email adress or a URL.',
					'html'        => array( 'class' => 'podlove-contributor-field podlove-check-input', 'data-podlove-input-type' => 'avatar' )
				)
			], 
			'slug' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'ID', 'podlove' ),
					'description' => 'The ID will be used as in internal identifier for e.g. shortcodes.',
					'html'        => array( 'class' => 'podlove-check-input required podlove-contributor-field' )
				)
			], 
			'guid' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'URI', 'podlove' ),
					'description' => __('An URI acts as a globally unique ID to identify contributors across podcasts on the internet.', 'podlove'),
					'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				)
			], 
			'visibility' => [
				'field_type' => 'radio',
				'field_options' => array(
					'label'       => __( 'Visibility', 'podlove' ),
					'options'	  => array( '1' => 'Yes, the contributor’s information will be visible for the public (e.g. displayed in the Contributor Table).<br />', 
						                    '0' => 'No, the contributor’s information will be private and not visible for anybody.' ),
					'default'	  => '1'
				)
			]
		];

		$general_fields = apply_filters('podlove_contributors_general_fields', $general_fields);

		$affiliation_fields = [
			'organisation' => [
				'field_type' => 'string',
				'field_options' => [
					'label' => __( 'Organisation', 'podlove' ),
					'html'  => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				]
			],
			'department' => [
				'field_type' => 'string',
				'field_options' => [
					'label' => __( 'Department', 'podlove' ),
					'html'  => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				]
			],
			'jobtitle' => [
				'field_type' => 'string',
				'field_options' => [
					'label' => __( 'Job Title', 'podlove' ),
					'html'  => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				]
			],
		];

		$affiliation_fields = apply_filters('podlove_contributors_affiliation_fields', $affiliation_fields);

		$form_sections = [
			'general' => [
				'title'  => __('General', 'podlove'),
				'fields' => $general_fields
			],
			'affiliation' => [
				'title'  => __('Affiliation', 'podlove'),
				'fields' => $affiliation_fields
			]
		];

		$form_sections = apply_filters('podlove_contributor_settings_sections', $form_sections);

		$contributor = \Podlove\Modules\Contributors\Model\Contributor::find_by_id( $_REQUEST['contributor'] );

		switch ( $_GET["action"] ) {
			case 'new':   $action = 'create';  break;
			case 'edit':  $action = 'save'; break;
			default:      $action = 'delete'; break;
		}

		$form_attributes = array(
			'context' => 'podlove_contributor',
			'action' => 'edit.php?post_type=podcast&page=podlove_contributors_settings_handle',
			'hidden'  => array(
				'contributor' => $contributor->id,
				'action' => $action
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

		echo '<h2>' . $heading . '</h2>';

		\Podlove\Form\build_for( $contributor, $form_attributes, function ( $form ) use ($form_sections) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$contributor = $form->object;

			foreach ($form_sections as $form_section) {
				$wrapper->subheader($form_section['title']);
				foreach ($form_section['fields'] as $field_name => $field) {
					call_user_func_array([$wrapper, $field['field_type']], [$field_name, $field['field_options']]);
				}
			}

		});

		// $tabs = new Tabs($heading);
		// $tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Contributors\General( __( 'General', 'podlove' ), true ) );
		// $tabs->addTab( new \Podlove\Modules\Contributors\Settings\Tab\Contributors\Affiliation( __( 'Affiliation', 'podlove' ) ) );
		// $this->tabs = apply_filters( 'podlove_contributor_settings_tabs', $tabs );
		// $this->tabs->initCurrentTab();

		// echo $this->tabs->getTabsHTML();
		// echo $this->tabs->getCurrentTabPage();
	}
	
	private function new_template() {
		$contributor = new Contributor;

		$this->contributor_form(__('New Contributor', 'podlove'));
	}
	
	private function edit_template() {
		$contributor = Contributor::find_by_id( $_REQUEST['contributor'] );

		$this->contributor_form(sprintf(
			'%s &#x00BB; %s', 
			'<a href="?post_type=podcast&amp;page=podlove_contributors_settings_handle" style="text-decoration: none">' . __('Contributors', 'podlove') . '</a>',
			$contributor->getName()
		));
	}

	public function scripts_and_styles() {
		wp_register_script( 'podlove-contributors-admin-script', \Podlove\PLUGIN_URL . '/lib/modules/contributors/js/admin.js', array( 'jquery-ui-autocomplete' ) );
		wp_enqueue_script( 'podlove-contributors-admin-script' );
	}
}
