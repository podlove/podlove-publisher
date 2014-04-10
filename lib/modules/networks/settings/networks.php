<?php
namespace Podlove\Modules\Networks\Settings;
use \Podlove\Modules\Networks\Model\Network;

class Networks {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Networks::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Networks',
			/* $menu_title */ 'Networks',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_network_handle',
			/* $function   */ array( $this, 'page' )
		);

		add_action( 'admin_init', array( $this, 'process_form' ) );
		add_action( 'admin_print_styles', array( $this, 'scripts_and_styles' ) );

	}

	public function process_form() {

		if ( ! isset( $_REQUEST['network'] ) )
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

	/**
	 * Parse Multiselect arguments into string
	 */
	private function manage_multiselect() {
		if( isset( $_POST['podlove_network']['podcasts'] ) && is_array( $_POST['podlove_network']['podcasts'] ) ) {
			$_POST['podlove_network']['podcasts'] = implode( ',', array_keys( $_POST['podlove_network']['podcasts'] ) );
		} else {
			$_POST['podlove_network']['podcasts'] = '';
		}		
	}

	/**
	 * Process form: save/update a network
	 */
	private function save() {
		if ( ! isset( $_REQUEST['network'] ) )
			return;

		self::manage_multiselect();
		$network = Network::find_by_id( $_REQUEST['network'] );
		$network->update_attributes( $_POST['podlove_network'] );
		
		$this->redirect( 'index', $network->id );
	}
	
	/**
	 * Process form: create a network
	 */
	private function create() {
		global $wpdb;
		
		self::manage_multiselect();
		$network = new Network;
		$network->update_attributes( $_POST['podlove_network'] );
		$this->redirect( 'index' );
	}
	
	/**
	 * Process form: delete a network
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['network'] ) )
			return;

		Network::find_by_id( $_REQUEST['network'] )->delete();
		
		$this->redirect( 'index' );
	}

	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $network_id = NULL ) {
		$page   = 'network/admin.php?page=' . $_REQUEST['page'];
		$show   = ( $network_id ) ? '&network=' . $network_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}

	public static function get_action_link( $network, $title, $action = 'edit', $class = 'link' ) {
		return sprintf(
			'<a href="?page=%s&amp;action=%s&amp;network=%s" class="%s">' . $title . '</a>',
			$_REQUEST['page'],
			$action,
			$network->id,
			$class
		);
	}

	private function view_template() {
		echo __( 'If you have configured a <a href="http://codex.wordpress.org/Create_A_Network">
				WordPress Network</a>, Podlove allows you to configure Podcast networks.', 'podlove' );
		$table = new \Podlove\Modules\Networks\Network_List_Table();
		$table->prepare_items();
		$table->display();
	}

	private function new_template() {
		$network = new Network;
		?>
		<h3><?php echo __( 'Add New Network', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $network, 'create', __( 'Add New Network', 'podlove' ) );
	}
	
	private function edit_template() {
		$network = Network::find_by_id( $_REQUEST['network'] );
		echo '<h3>' . sprintf( __( 'Edit Network: %s', 'podlove' ), $network->title ) . '</h3>';
		$this->form_template( $network, 'save' );
	}

	private function form_template( $network, $action, $button_text = NULL ) {

		$form_args = array(
			'context' => 'podlove_network',
			'hidden'  => array(
				'network' => $network->id,
				'action' => $action
			)
		);

		\Podlove\Form\build_for( $network, $form_args, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

			$network = $form->object;

			$wrapper->string( 'title', array(
				'label'       => __( 'Title', 'podlove' ),
				'html'        => array( 'class' => 'regular-text required' )
			) );

			$wrapper->string( 'subtitle', array(
				'label'       => __( 'Subtitle', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );

			$wrapper->text( 'description', array(
				'label'       => __( 'Description', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'html'        => array( 'rows' => 3, 'cols' => 40, 'class' => 'autogrow' )
			) );

			$wrapper->image( 'logo', array(
				'label'        => __( 'Logo', 'podlove' ),
				'description'  => __( 'JPEG or PNG.', 'podlove' ),
				'html'         => array( 'class' => 'regular-text' ),
				'image_width'  => 300,
				'image_height' => 300
			) );

			$wrapper->string( 'url', array(
				'label'       => __( 'Network URL', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );

			$podcasts = Network::all_podcasts_ordered();
			$podcasts_options_array = array();
			foreach ( $podcasts as $blog_id => $podcast ) {
				$podcasts_options_array[ $blog_id ] = $podcast->title;
			}

			$podcasts_multi_values = array_filter( $podcasts_options_array, function () {
				return 0;
			});
			
			foreach ( explode( ',', $network->podcasts ) as $podcast_id ) {
				$podcasts_multi_values[ $podcast_id ] = 1;
			}

			$wrapper->multiselect( 'podcasts', array(
				'label'       => __( 'Podcasts', 'podlove' ),
				'options'     => $podcasts_options_array,
				'multi_values'     => $podcasts_multi_values,
				'default'	  => false
			) );

		} );
	}

	function page() {
		if ( isset($_GET["action"]) AND $_GET["action"] == 'confirm_delete' AND isset( $_REQUEST['network'] ) ) {
			 $network = Network::find_by_id( $_REQUEST['network'] );
			?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf( __( 'You selected to delete the network "%s". Please confirm this action.', 'podlove' ), $network->title ) ?>
					</strong>
				</p>
				<p>
					<?php echo self::get_action_link( $network, __( 'Delete network permanently', 'podlove' ), 'delete', 'button' ) ?>
					<?php echo self::get_action_link( $network, __( 'Don\'t change anything', 'podlove' ), 'keep', 'button-primary' ) ?>
				</p>
			</div>
			<?php
		}
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Networks', 'podlove' ); ?> <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
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

	public function scripts_and_styles() {
		wp_register_style(
		    		'podlove_network_admin_style',
		    		\Podlove\PLUGIN_URL . '/lib/modules/networks/css/admin.css',
		    		false,
		    		\Podlove\get_plugin_header( 'Version' )
		    	);
		wp_enqueue_style('podlove_network_admin_style');
	}

}