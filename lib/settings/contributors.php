<?php
namespace Podlove\Settings;
use \Podlove\Model;

class Contributors {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Contributors::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Contributors',
			/* $menu_title */ 'Contributors',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_contributors_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'admin_print_styles', array( $this, 'scripts_and_styles' ) );
	}
	
	public static function get_action_link( $contributor, $title, $action = 'edit', $class = 'link' ) {
		return sprintf(
			'<a href="?page=%s&action=%s&contributor=%s" class="%s">' . $title . '</a>',
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
			 $contributor = \Podlove\Modules\Contributors\Contributor::find_by_id( $_REQUEST['contributor'] );
			?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf( __( 'You selected to delete the contributor "%s". Please confirm this action.', 'podlove' ), $contributor->realname ) ?>
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
			<h2><?php echo __( 'Contributors', 'podlove' ); ?> <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
		</div>	
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
	}
	
	/**
	 * Process form: save/update a contributor
	 */
	private function save() {
		if ( ! isset( $_REQUEST['contributor'] ) )
			return;
			
		$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id( $_REQUEST['contributor'] );
		$contributor->update_attributes( $_POST['podlove_contributor'] );
		
		$this->redirect( 'index', $contributor->id );
	}
	
	/**
	 * Process form: create a contributor
	 */
	private function create() {
		global $wpdb;
		
		$contributor = new \Podlove\Modules\Contributors\Contributor;
		$contributor->update_attributes( $_POST['podlove_contributor'] );

		$this->redirect( 'index' );
	}
	
	/**
	 * Process form: delete a contributor
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['contributor'] ) )
			return;

		\Podlove\Modules\Contributors\Contributor::find_by_id( $_REQUEST['contributor'] )->delete();
		
		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $contributor_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $contributor_id ) ? '&contributor=' . $contributor_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}
	
	private function view_template() {
		$table = new \Podlove\Contributor_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function new_template() {
		$contributor = new \Podlove\Modules\Contributors\Contributor
		?>
		<h3><?php echo __( 'Add New Contributor', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $contributor, 'create', __( 'Add New Contributor', 'podlove' ) );
	}
	
	private function edit_template() {
		$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id( $_REQUEST['contributor'] );
		echo '<h3>' . sprintf( __( 'Edit Contributor: %s', 'podlove' ), $contributor->realname ) . '</h3>';
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


			$wrapper->subheader( __( 'General', 'podlove' ) );
			
			$wrapper->checkbox( 'showpublic', array(
				'label'       => __( 'Public Profile', 'podlove' ),
				'description' => 'Check this, if you want the contributor\'s profile to appear e.g. in the Web Player.',
				'default'     => false
			) );

			$wrapper->checkbox( 'permanentcontributor', array(
				'label'       => __( 'Permanent Contributor', 'podlove' ),
				'description' => 'Check this, if you want the contributor to be added to each episode per default.',
				'default'     => false
			) );

			$wrapper->string( 'slug', array(
				'label'       => __( 'ID', 'podlove' ),
				'description' => 'The ID will be used as in internal identifier for e.g. shorttags.',
				'html'        => array( 'class' => 'required' )
			) );	

			$wrapper->string( 'realname', array(
				'label'       => __( 'Real name', 'podlove' ),
				'html'        => array( 'class' => 'required' )
			) );

			$wrapper->string( 'publicname', array(
				'label'       => __( 'Public name', 'podlove' ),
				'description' => 'The Public Name will be used for public mentions. E.g. the Web Player.'
			) );

			$wrapper->string( 'nickname', array(
				'label'       => __( 'Nickname', 'podlove' )
			) );

			$wrapper->string( 'guid', array(
				'label'       => __( 'GUID/URI', 'podlove' )
			) );		
			
			$wrapper->select( 'gender', array(
				'label'       => __( 'Gender', 'podlove' ),
				'options'     => array( 'female' => 'Female', 'male' => 'Male', 'none' => 'Not attributed')
			) );
			
			$wrapper->select( 'role', array(
				'label'       => __( 'Default role', 'podlove' ),
				'description' => 'The default role of the conributor.',
				'options'     => array( 'moderator' => 'Moderator',
										'comoderator' => 'Co-Moderator',
										'guest' => 'Guest',
										'camera' => 'Camera',
										'chatmoderator' => 'Chat-Moderator',
										'shownoter' => 'Shownoter')
			) );

			$wrapper->string( 'avatar', array(
				'label'       => __( 'Avatar', 'podlove' ),
				'description' => 'Either a Gravatar E-mail adress or a URL.'
			) );
			
			$wrapper->subheader( __( 'Affiliation', 'podlove' ) );
			
			$wrapper->string( 'organisation', array(
				'label'       => __( 'Organisation', 'podlove' )
			) );
			
			$wrapper->string( 'department', array(
				'label'       => __( 'Department', 'podlove' )
			) );
			
			$wrapper->subheader( __( 'Contact &amp; Social', 'podlove' ) );
			
			$wrapper->string( 'privateemail', array(
				'label'       => __( 'Private E-mail', 'podlove' ),
				'description' => 'The provided E-mail will be used for internal purposes only.',
			) );
			
			$wrapper->string( 'publicemail', array(
				'label'       => __( 'Public E-mail', 'podlove' ),
				'description' => 'This E-mail will be displayed for public purposes.'
			) );		

			$wrapper->string( 'www', array(
				'label'       => __( 'Homepage', 'podlove' ),
				'description' => 'The contributors homepage.'
			) );

			$wrapper->string( 'adn', array(
				'label'       => __( 'App.net', 'podlove' ),
				'description' => 'App.net username.'
			) );	
			
			$wrapper->string( 'twitter', array(
				'label'       => __( 'Twitter', 'podlove' ),
				'description' => 'Twitter username.'
			) );				
				
			$wrapper->string( 'flattr', array(
				'label'       => __( 'Flattr', 'podlove' ),
				'description' => 'Flattr username.'
			) );	
			
			$wrapper->string( 'facebook', array(
				'label'       => __( 'Facebook', 'podlove' ),
				'description' => 'Facebook URL.'
			) );	
			
			$wrapper->string( 'wishlist', array(
				'label'       => __( 'Wishlist', 'podlove' ),
				'description' => 'URL of the contributors wishlist (e.g. Amazon).'
			) );	

		} );
	}
	
	public function register_metabox() {		
		add_meta_box(  'tagsdiv-podlove-contributors-hide',
						 __( 'Contributors', 'podlove' ), 
						array( '\Podlove\Settings\Contributors', 'metabox' ), 
						'podcast', 
						'side', 
						'default' );  
		
	}
	
	public function metabox( $post ) {
		?>
		<div id="add_contributors" class="tagsdiv">
			<p>
				<input type="text" class="newtag" id="add_contributors_input">
				<input type="button" class="button tagadd" id="add_contributors_submit" value="Add">
			</p>
		</div>
		<div id="contributors" class="tagchecklist">
			<div class="nojs-tags hide-if-js">
				<p><?php echo __( 'Add or remove contributors', 'podlove' ) ?></p>
				<textarea name="tax_input[podlove-contributors]" rows="3" cols="20" class="the-contributors" id="tax-input-podlove-contributors">
				</textarea>
				<input type="hidden" name="_podlove_contributors" id="_podlove_contributors" />
			</div>
		</div>
		<script type="text/javascript">
		<?php
			$contributors = \Podlove\Modules\Contributors\Contributor::all();
			$people = Array();
			
			foreach($contributors as $contributor_id => $contributer_values) {
				$people[$contributor_id] = array(
							'value'  => $contributer_values->slug,
							'label'  => $contributer_values->realname,
							'id'     => $contributer_values->id,
							'avatar' => \Podlove\Settings\Contributors::get_gravatar_url($contributer_values->privateemail, 24)
				);
			}			
			
		?>
		var PODLOVE = PODLOVE || {};
		PODLOVE.people = <?php echo json_encode($people); ?>;
		</script>
		<?php
	}
	
	public function scripts_and_styles() {
		wp_register_script( 'podlove-contributors-admin-script', get_bloginfo('url') . '/wp-content/plugins/podlove-publisher/lib/modules/contributors/js/admin.js', array( 'jquery-ui-autocomplete' ) );
		wp_enqueue_script( 'podlove-contributors-admin-script' );

		wp_register_style( 'podlove-contributors-admin-style', get_bloginfo('url') . '/wp-content/plugins/podlove-publisher/lib/modules/contributors/css/admin.css' );
		wp_enqueue_style( 'podlove-contributors-admin-style' );
	}
	
	public function get_gravatar_url( $email, $s = 80, $d = 'mm', $r = 'g', $atts = array() ) {
		
		$url = 'http://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s=$s&d=$d&r=$r";
		return $url;
	}
}
