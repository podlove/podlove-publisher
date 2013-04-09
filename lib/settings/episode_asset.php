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

		register_setting( EpisodeAsset::$pagehook, 'podlove_asset_assignment' );
	}
	
	/**
	 * Process form: save/update a format
	 */
	private function save() {

		if ( ! isset( $_REQUEST['episode_asset'] ) )
			return;

		$episode_asset = \Podlove\Model\EpisodeAsset::find_by_id( $_REQUEST['episode_asset'] );
		$episode_asset->update_attributes( $_POST['podlove_episode_asset'] );
		
		$this->redirect( 'index', $episode_asset->id );
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

		$podcast = Model\Podcast::get_instance();
		$asset   = Model\EpisodeAsset::find_by_id( $_REQUEST['episode_asset'] );

		if ( isset( $_REQUEST['force'] ) && $_REQUEST['force'] || $asset->is_deletable() ) {
			$asset->delete();
			$this->redirect( 'index' );
		} else {
			$this->redirect( 'index', NULL, array( 'message' => 'media_file_relation_warning', 'deleted_id' => $asset->id ) );
		}
		
	}
	
	public function batch_enable() {

		if ( ! isset( $_REQUEST['episode_asset'] ) )
			return;

		$podcast = Model\Podcast::get_instance();
		$asset   = Model\EpisodeAsset::find_by_id( $_REQUEST['episode_asset'] );

		$episodes = Model\Episode::all();
		foreach ( $episodes as $episode ) {

			$post_id = $episode->post_id;
			$post = get_post( $post_id );

			// skip deleted podcasts
			if ( ! in_array( $post->post_status, array( 'draft', 'publish', 'future' ) ) )
				continue;

			// skip versions
			if ( $post->post_type != 'podcast' )
				continue;

			$file = Model\MediaFile::find_by_episode_id_and_episode_asset_id( $episode->id, $asset->id );

			if ( $file === NULL ) {
				$file = new Model\MediaFile();
				$file->episode_id = $episode->id;
				$file->episode_asset_id = $asset->id;
				$file->save();
			}
		}

		$this->redirect( 'index', NULL, array( 'message' => 'media_file_batch_enabled_notice' ) );
	}

	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $episode_asset_id = NULL, $params = array() ) {
		$page    = 'admin.php?page=' . $_REQUEST['page'];
		$show    = ( $episode_asset_id ) ? '&episode_asset=' . $episode_asset_id : '';
		$action  = '&action=' . $action;

		array_walk( $params, function(&$value, $key) { $value = "&$key=$value"; } );
		
		wp_redirect( admin_url( $page . $show . $action . implode( '', $params ) ) );
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
		} elseif ( $action === 'batch_enable' ) {
			$this->batch_enable();
		}
	}
	
	public function page() {
		if ( isset( $_REQUEST['message'] ) ) {
			if ( $_REQUEST['message'] == 'media_file_batch_enabled_notice' ) {
				?>
				<div class="updated">
					<p><?php echo __( '<strong>Media Files enabled.</strong> These Media Files have been enabled for all existing episodes.' ) ?></p>
				</div>
				<?php
			}
			if ( $_REQUEST['message'] == 'media_file_relation_warning' ) {
				$asset = Model\EpisodeAsset::find_one_by_id( (int) $_REQUEST['deleted_id'] );
				?>
				<div class="error">
					<p>
						<?php echo __( '<strong>The asset has not been deleted. Are you aware that the asset is still in use?</strong>', 'podlove' ) ?>
						<ul class="ul-disc">
							<?php if ( $asset->has_active_media_files() ): ?>
								<li>
									<?php echo sprintf( __( 'There are %s connected media files.', 'podlove' ), count( $asset->active_media_files() ) ) ?>
								</li>
							<?php endif; ?>
							<?php if ( $asset->has_asset_assignments() ): ?>
								<li>
									<?php echo __( 'This asset is assigned to episode images or episode chapters.', 'podlove' ) ?>
								</li>
							<?php endif; ?>
							<?php if ( $asset->is_connected_to_feed() ): ?>
								<li>
									<?php echo __( 'A feed uses this asset.', 'podlove' ) ?>
								</li>
							<?php endif; ?>
							<?php if ( $asset->is_connected_to_web_player() ): ?>
								<li>
									<?php echo __( 'The web player uses this asset.', 'podlove' ) ?>
								</li>
							<?php endif; ?>
						</ul>
						<a href="?page=<?php echo $_REQUEST['page'] ?>&amp;action=delete&amp;episode_asset=<?php echo $asset->id ?>&amp;force=1">
							<?php echo __( 'delete anyway', 'podlove' ) ?>
						</a>
					</p>
				</div>
				<?php
			}
		}
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
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

		?>
		<h3><?php echo __( 'Assign Assets', 'podlove' ) ?></h3>
		<form method="post" action="options.php">
			<?php settings_fields( EpisodeAsset::$pagehook );
			$asset_assignment = Model\AssetAssignment::get_instance();

			$form_attributes = array(
				'context'    => 'podlove_asset_assignment',
				'form'       => false
			);

			\Podlove\Form\build_for( $asset_assignment, $form_attributes, function ( $form ) {
				$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
				$asset_assignment = $form->object;
				$artwork_options = array(
					'0'      => __( 'None', 'podlove' ),
					'manual' => __( 'Manual Entry', 'podlove' ),
				);
				$episode_assets = Model\EpisodeAsset::all();
				foreach ( $episode_assets as $episode_asset ) {
					$file_type = $episode_asset->file_type();
					if ( $file_type && $file_type->type === 'image' ) {
						$artwork_options[ $episode_asset->id ] = sprintf( __( 'Asset: %s', 'podlove' ), $episode_asset->title );
					}
				}

				$wrapper->select( 'image', array(
					'label'   => __( 'Episode Image', 'podlove' ),
					'options' => $artwork_options
				) );

				$chapter_file_options = array(
					'0'      => __( 'None', 'podlove' ),
					'manual' => __( 'Manual Entry', 'podlove' )
				);
				$episode_assets = Model\EpisodeAsset::all();
				foreach ( $episode_assets as $episode_asset ) {
					$file_type = $episode_asset->file_type();
					if ( $file_type && $file_type->type === 'chapters' ) {
						$chapter_file_options[ $episode_asset->id ] = sprintf( __( 'Asset: %s', 'podlove' ), $episode_asset->title );
					}
				}
				$wrapper->select( 'chapters', array(
					'label'   => __( 'Episode Chapters', 'podlove' ),
					'options' => $chapter_file_options
				) );

				do_action( 'podlove_asset_assignment_form', $wrapper, $asset_assignment );
			});
		?>
		</form>
		<?php
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
				'attributes' => 'data-type="' . $f['type'] . '" data-extension="' . $f['extension'] . '"'
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
					<div id="url_template" style="display: none;"><?php echo Model\Podcast::get_instance()->get_url_template() ?></div>
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
