<?php 
namespace Podlove\Settings;
use \Podlove\Model;

class EpisodeAsset {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		self::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ __( 'Episode Assets', 'podlove' ),
			/* $menu_title */ __( 'Episode Assets', 'podlove' ),
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_episode_assets_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}
	
	/**
	 * Process form: save/update a format
	 */
	private function save() {
		if ( ! isset( $_REQUEST['episode_asset'] ) )
			return;
			
		$episode_asset = \Podlove\Model\EpisodeAsset::find_by_id( $_REQUEST['episode_asset'] );
		$episode_asset->update_attributes( $_POST['podlove_episode_asset'] );
		
		$this->redirect( 'edit', $episode_asset->id );
	}
	
	/**
	 * Process form: create a format
	 */
	private function create() {
		global $wpdb;
		
		$episode_asset = new \Podlove\Model\EpisodeAsset;
		$episode_asset->update_attributes( $_POST['podlove_episode_asset'] );

		$this->redirect( 'index' );
	}
	
	/**
	 * Process form: delete a format
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['episode_asset'] ) )
			return;

		\Podlove\Model\EpisodeAsset::find_by_id( $_REQUEST['episode_asset'] )->delete();
		
		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $episode_asset_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $episode_asset_id ) ? '&episode_asset=' . $episode_asset_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}
	
	public function process_form() {

		if ( ! isset( $_REQUEST['episode_asset'] ) )
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
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php echo __( 'Episode Assets', 'podlove' ); ?> <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
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
		$episode_asset = new \Podlove\Model\EpisodeAsset;
		?>
		<h3><?php echo __( 'Add New Episode Asset', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $episode_asset, 'create', __( 'Add New Episode Asset', 'podlove' ) );
	}
	
	private function view_template() {
		$table = new \Podlove\Episode_Asset_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function form_template( $episode_asset, $action, $button_text = NULL ) {

		$raw_formats = \Podlove\Model\FileType::all();
		$formats = array();
		foreach ( $raw_formats as $format ) {
			$formats[ $format->id ] = array(
				'title'     => $format->title(),
				'extension' => $format->extension,
				'type'      => $format->type
			);
		}

		$format_optionlist = array_map( function ( $f ) {
			return array(
				'value'      => $f['title'],
				'attributes' => 'data-type="' . $f['type'] . '"'
			);
		}, $formats );

		$form_args = array(
			'context' => 'podlove_episode_asset',
			'hidden'  => array(
				'episode_asset' => $episode_asset->id,
				'action' => $action
			),
			'attributes' => array(
				'id' => 'podlove_episode_assets'
			)
		);

		\Podlove\Form\build_for( $episode_asset, $form_args, function ( $form ) use ( $format_optionlist ) {
			$f = new \Podlove\Form\Input\TableWrapper( $form );
			if ( $form->object->file_type_id ) {
				$current_file_type = Model\FileType::find_by_id( $form->object->file_type_id )->type;
			} else {
				$current_file_type = '';
			}
			?>
			<tr class="row_podlove_episode_asset_type">
				<th scope="row" valign="top">
					<label for="podlove_episode_asset_type"><?php echo __( 'Asset Type', 'podlove' ); ?></label>
				</th>
				<td>
					<select name="podlove_episode_asset_type" id="podlove_episode_asset_type">
						<option><?php echo __( 'Please choose ...', 'podlove' ); ?></option>
						<?php foreach ( Model\FileType::get_types() as $type ): ?>
							<option value="<?php echo $type ?>" <?php selected( $type, $current_file_type ) ?>><?php echo $type ?></option>	
						<?php endforeach; ?>
					</select>
					<div id="option_storage"></div>
				</td>
			</tr>
			<?php

			$f->select( 'file_type_id', array(
				'label'       => __( 'File Format', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'options'     => $format_optionlist
			) );

			$f->string( 'title', array(
				'label'       => __( 'Title', 'podlove' ),
				'description' => __( 'Description to identify the media file type to the user in download buttons.', 'podlove' ),
				'html' => array( 'class' => 'regular-text required' )
			) );

			$f->string( 'suffix', array(
				'label'       => __( 'Suffix', 'podlove' ),
				'description' => __( 'Optional. Is appended to file name after episode slug.', 'podlove' ),
				'html' => array( 'class' => 'regular-text required' )
			) );

			?>
			<tr class="row_podlove_asset_url_preview">
				<th scope="row" valign="top">
					<label for="podlove_asset_url_preview"><?php echo __( 'URL Preview', 'podlove' ); ?></label>
				</th>
				<td>
					<div id="url_preview" style="font-size: 1.5em"></div>
					<div id="url_template" style="display: none;"><?php echo Model\Podcast::get_instance()->url_template ?></div>
				</td>
			</tr>
			<?php

			$f->checkbox( 'downloadable', array(
				'label'       => __( 'Downloadable', 'podlove' ),
				'description' => sprintf( 'Allow downloads for users.', 'podlove' ),
				'default' => true
			) );

		} );

		// hidden fields for JavaScript
		?>
		<input type="hidden" id="podlove_show_media_file_base_uri" value="<?php echo Model\Podcast::get_instance()->media_file_base_uri; ?>">
		<?php
	}
	
	private function edit_template() {
		$episode_asset = \Podlove\Model\EpisodeAsset::find_by_id( $_REQUEST['episode_asset'] );
		echo '<h3>' . sprintf( __( 'Edit Episode Asset: %s', 'podlove' ), $episode_asset->title ) . '</h3>';
		$this->form_template( $episode_asset, 'save' );
	}

}