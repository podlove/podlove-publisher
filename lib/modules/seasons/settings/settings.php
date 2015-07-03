<?php 
namespace Podlove\Modules\Seasons\Settings;

use \Podlove\Modules\Seasons\Model\Season;
use \Podlove\Modules\Seasons\Model\SeasonsValidator;

class Settings {

	public function __construct($handle) {
		$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ __('Seasons', 'podlove'),
			/* $menu_title */ __('Seasons', 'podlove'),
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_seasons_settings',
			/* $function   */ [$this, 'page']
		);

		add_action( 'admin_init', array( $this, 'process_form' ) );
		add_action( "load-" . $pagehook, [$this, 'add_screen_options'] );
	}

	public function add_screen_options() {
		add_screen_option( 'per_page', array(
		   'label'   => 'Seasons',
		   'default' => 10,
		   'option'  => 'podlove_seasons_per_page'
		) );

		$this->table = new SeasonListTable;
	}

	/**
	 * Process form: save/update a format
	 */
	private function save() {

		if ( ! isset( $_REQUEST['season'] ) )
			return;

		$season = Season::find_by_id( $_REQUEST['season'] );
		$season->update_attributes( $_POST['podlove_season'] );
		
		if (isset($_POST['submit_and_stay'])) {
			$this->redirect( 'edit', $season->id );
		} else {
			$this->redirect( 'index', $season->id );
		}
	}
	
	/**
	 * Process form: create a format
	 */
	private function create() {
		global $wpdb;

		$season = new Season;
		$season->update_attributes( $_POST['podlove_season'] );

		if (isset($_POST['submit_and_stay'])) {
			$this->redirect( 'edit', $season->id );
		} else {
			$this->redirect( 'index' );
		}
	}
	
	/**
	 * Process form: delete a format
	 */
	private function delete() {

		if ( ! isset( $_REQUEST['season'] ) )
			return;

		// $podcast = Model\Podcast::get();
		// $asset   = Model\EpisodeAsset::find_by_id( $_REQUEST['season'] );

		// if ( isset( $_REQUEST['force'] ) && $_REQUEST['force'] || $asset->is_deletable() ) {
		// 	$asset->delete();
		// 	$this->redirect( 'index' );
		// } else {
		// 	$this->redirect( 'index', NULL, array( 'message' => 'media_file_relation_warning', 'deleted_id' => $asset->id ) );
		// }
		
	}

	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $episode_asset_id = NULL, $params = array() ) {
		$page    = 'admin.php?page=' . $_REQUEST['page'];
		$show    = ( $episode_asset_id ) ? '&season=' . $episode_asset_id : '';
		$action  = '&action=' . $action;

		array_walk( $params, function(&$value, $key) { $value = "&$key=$value"; } );
		
		wp_redirect( admin_url( $page . $show . $action . implode( '', $params ) ) );
		exit;
	}
	
	public function process_form() {

		if ( ! isset( $_REQUEST['season'] ) )
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
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Seasons', 'podlove' ); ?> <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
			<?php
			$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : NULL;
			switch ( $action ) {
				case 'new':   $this->new_template();  break;
				case 'edit':  $this->edit_template(); break;
				case 'index': $this->view_template(); break;
				default:      $this->view_template(); break;
			}
			?>
		</div>	
		<?php
	}
	
	private function new_template() {
		$season = new Season;
		?>
		<h3><?php echo __( 'Add New Season', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $season, 'create', __( 'Add New Season', 'podlove' ) );
	}
	
	private function view_template() {

		$validator = new SeasonsValidator;
		$validator->validate();
		$issues = $validator->issues();
		foreach ($validator->issues() as $issue) {
			?>
			<div class="error">
				<p>
					<strong><?php echo __('Warning', 'podlove') . ': ' ?></strong>
					<?php echo $issue->message(); ?>
				</p>
			</div>
			<?php
		}

		$this->table->prepare_items();
		$this->table->display();
	}
	
	private function form_template( $season, $action, $button_text = NULL ) {

		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-datepicker');

		$form_args = array(
			'context' => 'podlove_season',
			'hidden'  => array(
				'season'      => $season->id,
				'action'      => $action
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

		\Podlove\Form\build_for( $season, $form_args, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$season  = $form->object;

	 		$wrapper->string('title', [
	 			'label'       => __('Title', 'podlove'),
	 			'html'        => ['class' => 'regular-text podlove-check-input']
	 		]);

	 		$wrapper->string('subtitle', [
	 			'label'       => __('Subtitle', 'podlove'),
	 			'html'        => ['class' => 'regular-text podlove-check-input']
	 		]);

	 		$wrapper->text('summary', [
	 			'label'       => __('Summary', 'podlove'),
	 			'html'        => array( 'rows' => 3, 'cols' => 40, 'class' => 'autogrow podlove-check-input' )
	 		]);

	 		$wrapper->string('start_date', [
	 			'label'       => __('Start Date', 'podlove'),
	 			'html'        => ['class' => 'regular-text podlove-check-input', 'readonly' => 'readonly']
	 		]);

	 		$wrapper->upload('image', [
	 			'label' => __('Image', 'podlove'),
	 			'html'  => ['class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'url'],
 				'media_button_text' => __("Use Image for Season", 'podlove')
	 		]);
		} );

	}
	
	private function edit_template() {
		$season = Season::find_by_id( $_REQUEST['season'] );
		echo '<h3>' . sprintf( __( 'Edit Season: %s', 'podlove' ), $season->title ) . '</h3>';
		$this->form_template( $season, 'save' );
	}

}
