<?php
namespace Podlove\Modules\Contributors\Settings;

/**
 * Provide a standard settings page for an entity with:
 *
 * 1) list table view
 * 2) edit form per item
 */
class GenericEntitySettings {

	private $entity_slug;
	private $entity_class;
	private $form_callback;
	private $labels = array();

	private $is_tab = false;
	private $tab_slug = '';

	public function __construct($entity_slug, $entity_class) {

		$this->entity_slug  = $entity_slug;
		$this->entity_class = $entity_class;

		$default_labels = array(
			'delete_confirm' => __( 'You selected to delete the entity "%s". Please confirm this action.', 'podlove' ),
			'delete_button_delete' => __( 'Delete permanently', 'podlove' ),
			'delete_button_keep'   => __( 'Don\'t change anything', 'podlove' ),
			'add_new' => __( 'Add New', 'podlove' ),
			'edit'    => __( 'Edit', 'podlove' )
		);

		$this->labels = $default_labels;

		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	private function get_entity_slug() {
		return $this->entity_slug;
	}

	private function get_entity_class() {
		return $this->entity_class;
	}

	public function enable_tabs($tab_slug) {
		$this->is_tab   = true;
		$this->tab_slug = $tab_slug;
	}

	public function set_labels($labels) {
		$this->labels = wp_parse_args( $labels, $this->labels );
	}

	public function set_form($form_callback) {
		$this->form_callback = $form_callback;
	}

	public function process_form() {

		if ( !isset( $_REQUEST[ $this->get_entity_slug() ] ) )
			return;

		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : NULL;

		if ( $action === 'save' ) {
			$this->save();
		} elseif ( $action === 'create' ) {
			$this->create();
		} elseif ( $action === 'delete' ) {
			$this->delete();
		}
	}

	/**
	 * Process form: save/update entity
	 */
	protected function save() {
		if ( ! isset( $_REQUEST[ $this->get_entity_slug() ] ) )
			return;

		$class = $this->get_entity_class();

		$entity = $class::find_by_id($_REQUEST[ $this->get_entity_slug() ]);
		$entity->update_attributes( $_POST['podlove_' . $this->get_entity_slug()] );
		
		if (isset($_POST['submit_and_stay'])) {
			$this->redirect( 'edit', $entity->id );
		} else {
			$this->redirect( 'index', $entity->id );
		}
	}

	/**
	 * Process form: create entity
	 */
	protected function create() {
		global $wpdb;

		$class = $this->get_entity_class();
		
		$entity = new $class;
		$entity->update_attributes( $_POST['podlove_' . $this->get_entity_slug()] );

		if (isset($_POST['submit_and_stay'])) {
			$this->redirect( 'edit', $entity->id );
		} else {
			$this->redirect( 'index' );
		}
	}

	/**
	 * Process form: delete a contributor
	 */
	protected function delete() {

		if ( !isset( $_REQUEST[ $this->get_entity_slug() ] ) )
			return;

		$class = $this->get_entity_class();
		$class::find_by_id( $_REQUEST[ $this->get_entity_slug() ] )->delete();
		
		$this->redirect('index');
	}

	public function page() {
		if ( isset($_GET["action"]) AND $_GET["action"] == 'confirm_delete' AND isset( $_REQUEST[ $this->get_entity_slug() ] ) ) {
			$class  = $this->get_entity_class();
			$entity = $class::find_by_id( $_REQUEST[ $this->get_entity_slug() ] );
			?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf($this->labels['delete_confirm'], $entity->title); ?>
					</strong>
				</p>
				<p>
					<?php echo self::get_action_link( $this->get_entity_slug(), $entity->id, $this->labels['delete_button_delete'], 'delete', 'button' ) ?>
					<?php echo self::get_action_link( $this->get_entity_slug(), $entity->id, $this->labels['delete_button_keep'], 'keep', 'button-primary' ) ?>
				</p>
			</div>
			<?php
		}
		
		?>
		<div class="wrap">
			<?php
				do_action('podlove_settings_' . $this->entity_slug . '_before');
				
				if (isset($_GET["action"])) {
					switch ( $_GET["action"] ) {
						case 'new':   $this->new_template();  break;
						case 'edit':  $this->edit_template(); break;
						default:      $this->view_template(); break;
					}
				} else {
					$this->view_template();
				}

				do_action('podlove_settings_' . $this->entity_slug);
			?>
		</div>	
		<?php
	}

	protected function new_template() {
		$class  = $this->get_entity_class();
		$entity = new $class;

		echo '<h3>' . $this->labels['add_new'] . '</h3>';
		do_action('podlove_settings_' . $this->entity_slug . '_new_before');
		$this->form_template( $entity, 'create' );
		do_action('podlove_settings_' . $this->entity_slug . '_new');
	}

	protected function edit_template() {
		$class  = $this->get_entity_class();
		$entity = $class::find_by_id( $_REQUEST[ $this->get_entity_slug() ] );
		echo '<h3>' . $this->labels['edit'] . '</h3>';
		do_action('podlove_settings_' . $this->entity_slug . '_edit_before');
		$this->form_template( $entity, 'save' );
		do_action('podlove_settings_' . $this->entity_slug . '_edit');
	}

	protected function view_template() {
		$tab = $this->is_tab ? '&amp;podlove_tab=' . $this->tab_slug : '';
		?>
		<h2>
			<a href="?page=<?php echo $_REQUEST['page'] . $tab; ?>&amp;action=new" class="add-new-h2"><?php echo $this->labels['add_new']; ?></a>
		</h2>
		<?php
		do_action('podlove_settings_' . $this->entity_slug . '_view');
	}

	private function form_template($entity, $action) {

		$form_args = array(
			'context' => 'podlove_' . $this->get_entity_slug(),
			'hidden'  => array( 'action' => $action ),
			'submit_button' => false, // for custom control in form_end
			'form_end' => function() {
				echo "<p>";
				submit_button( __('Save Changes', 'podlove'), 'primary', 'submit', false );
				echo " ";
				submit_button( __('Save Changes and Continue Editing', 'podlove'), 'secondary', 'submit_and_stay', false );
				echo "</p>";
			}
		);

		$form_args['hidden'][$this->get_entity_slug()] = $entity->id;

		$cb = $this->form_callback;
		$cb($form_args, $entity, $action);
	}

	public static function get_action_link( $entity_slug, $id, $title, $action = 'edit', $class = 'link' ) {
		$request = ( isset( $_REQUEST['podlove_tab'] ) ? "&amp;podlove_tab=".$_REQUEST['podlove_tab'] : '' );
		return sprintf(
			'<a href="?page=%s%s&amp;action=%s&amp;%s=%s" class="%s">' . $title . '</a>',
			$_REQUEST['page'],
			$request,
			$action,
			$entity_slug,
			$id,
			$class
		);
	}

	/**
	 * Helper method: redirect to a certain page.
	 */
	protected function redirect( $action, $entity_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = $entity_id ? '&' . $this->get_entity_slug() . '=' . $entity_id : '';
		$action = '&action=' . $action;
		$tab    = $this->is_tab ? '&podlove_tab=' . $this->tab_slug : '';
		
		wp_redirect( admin_url( $page . $show . $action . $tab ) );
		exit;
	}
}
