<?php
class Podlove_Show_Settings_Page {
	
	protected $field_keys;
	
	public function __construct( $handle ) {
		
		$this->field_keys = array(
			'name' => array(
				'label'       => Podlove::t( 'Name' ),
				'description' => Podlove::t( '' )
			),
			'slug' => array(
				'label'       => Podlove::t( 'Slug' ),
				'description' => Podlove::t( '' )
			),
			'subtitle' => array(
				'label'       => Podlove::t( 'Show Subtitle' ),
				'description' => Podlove::t( 'The subtitle is used by iTunes.' )
			),
			'label' => array(
				'label'       => Podlove::t( 'Show Label' ),
				'description' => Podlove::t( 'The show label is the prefix for every show title. It should be all caps and 3 or 4 characters long. Example: POD' )
			),
			'episode_prefix' => array(
				'label'       => Podlove::t( 'Episode Prefix' ),
				'description' => Podlove::t( 'Slug for file URI. Example: pod_' )
			),
			'media_file_base_uri' => array(
				'label'       => Podlove::t( 'Media File Base URI' ),
				'description' => Podlove::t( 'Example: http://cdn.example.com/pod/' )
			),
			'uri_delimiter' => array(
				'label'       => Podlove::t( 'URI Delimiter' ),
				'description' => Podlove::t( 'Example: -' )
			),
			'episode_number_length' => array(
				'label'       => Podlove::t( 'Episode Number Length' ),
				'description' => Podlove::t( 'If the episode number has fewer digits than defined here, it will be prefixed with leading zeroes. Example: 3' )
			)
		);
		
		add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Shows',
			/* $menu_title */ 'Shows',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_shows_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}
	
	public function process_form() {
		global $wpdb;
		
		$action = ( isset( $_REQUEST[ 'action' ] ) ) ? $_REQUEST[ 'action' ] : NULL;
		
		if ( $action === 'save' ) {
			if ( ! isset( $_REQUEST[ 'show' ] ) )
				return;
				
			$show = Podlove_Show::find_by_id( $_REQUEST[ 'show' ] );
			
			if ( ! isset( $_POST[ 'podlove_show' ] ) || ! is_array( $_POST[ 'podlove_show' ] ) )
				return;
				
			foreach ( $_POST[ 'podlove_show' ] as $key => $value ) {
				$show->{$key} = $value;
			}
			$show->save();
			
			if ( isset( $_POST[ 'podlove_show_format' ] ) && is_array( $_POST[ 'podlove_show_format' ] ) ) {
				$show_formats = get_option( '_podlove_show_formats' );
				if ( ! isset( $show_formats ) || ! is_array( $show_formats ) )
					$show_formats = array();
					
				$show_formats[ $show->id ] = array_keys( $_POST[ 'podlove_show_format' ] );
				update_option( '_podlove_show_formats', $show_formats );
			}
			
			wp_redirect(
				admin_url(
					'admin.php?page=' . $_REQUEST[ 'page' ]
					. '&show=' . $show->id
					. '&action=edit'
				)
			);
			exit;
		} elseif ( $action === 'create' ) {
			$show = new Podlove_Show;
			
			if ( ! isset( $_POST[ 'podlove_show' ] ) || ! is_array( $_POST[ 'podlove_show' ] ) )
				return;
				
			foreach ( $_POST[ 'podlove_show' ] as $key => $value ) {
				$show->{$key} = $value;
			}
			$show->save();
			
			if ( isset( $_POST[ 'podlove_show_format' ] ) && is_array( $_POST[ 'podlove_show_format' ] ) ) {
				$show_formats = get_option( '_podlove_show_formats' );
				if ( ! isset( $show_formats ) || ! is_array( $show_formats ) )
					$show_formats = array();
					
				$show_formats[ $show->id ] = array_keys( $_POST[ 'podlove_show_format' ] );
				update_option( '_podlove_show_formats', $show_formats );
			}
			
			wp_redirect(
				admin_url(
					'admin.php?page=' . $_REQUEST[ 'page' ]
					. '&show=' . $wpdb->insert_id
					. '&action=edit'
				)
			);
			exit;
		} elseif ( $action === 'delete' ) {
			if ( ! isset( $_REQUEST[ 'show' ] ) )
				return;
				
			$show = Podlove_Show::find_by_id( $_REQUEST[ 'show' ] );
			
			$show_formats = get_option( '_podlove_show_formats' );
			if ( ! isset( $show_formats ) || ! is_array( $show_formats ) )
				$show_formats = array();
				
			unset( $show_formats[ $show->id ] );
			update_option( '_podlove_show_formats', $show_formats );
			
			$show->delete();

			wp_redirect(
				admin_url(
					'admin.php?page=' . $_REQUEST[ 'page' ]
					. '&action=index'
				)
			);
			exit;
		}
	}
	
	public function page() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2>Podlove Shows <a href="?page=<?php echo $_REQUEST[ 'page' ]; ?>&amp;action=new" class="add-new-h2"><?php echo Podlove::t( 'Add New' ); ?></a></h2>
			<?php
			$action = ( isset( $_REQUEST[ 'action' ] ) ) ? $_REQUEST[ 'action' ] : NULL;
			switch ( $action ) {
				case 'new':
					$this->new_template();
					break;
				case 'edit':
					$this->edit_template();
					break;
				case 'index':
				default:
					$this->view_template();
					break;
			}
			?>
		</div>	
		<?php
	}
	
	private function new_template() {
		$show = new Podlove_Show;
		?>
		<h3><?php echo Podlove::t( 'Add New Show' ); ?></h3>
		<?php
		$this->form_template( $show, 'create', Podlove::t( 'Add New Show' ) );
	}
	
	private function view_template() {
		$table = new Podlove_Show_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function form_template( $show, $action, $button_text = NULL ) {
		?>
		<form action="<?php echo admin_url( 'admin.php?page=' . $_REQUEST[ 'page' ] ) ?>" method="post">
			<input type="hidden" name="show" value="<?php echo $show->id ?>" />
			<input type="hidden" name="action" value="<?php echo $action; ?>" />
			<table class="form-table">
				<?php
				foreach ( $this->field_keys as $key => $value ): ?>
					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="<?php echo $key; ?>"><?php echo $this->field_keys[ $key ][ 'label' ]; ?></label>
						</th>
						<td>
							<input type="text" name="podlove_show[<?php echo $key; ?>]" value="<?php echo $show->{$key}; ?>" id="<?php echo $key; ?>">
							<br />
							<span class="description"><?php echo $this->field_keys[ $key ][ 'description' ]; ?></span>
						</td>
					</tr>
				<?php
				endforeach;
				?>
				<tr>
					<th scope="row" valign="top">
						<label for="formats"><?php echo Podlove::t( 'Formats' ) ?></label>
					</th>
					<td>
						<?php
						$formats = Podlove_Format::all();
						$show_formats = get_option( '_podlove_show_formats' );
						if ( ! isset( $show_formats ) || ! is_array( $show_formats ) )
							$show_formats = array();
							
						if ( isset( $show_formats[ $show->id ] ) )
							$this_show_formats = $show_formats[ $show->id ];
						else
							$this_show_formats = array();
						
						?>
						<?php foreach ( $formats as $format ): ?>
							<?php $id = 'podlove_show_format_' . $format->id; ?>
							<label for="<?php echo $id; ?>">
								<input
									type="checkbox"
									name="podlove_show_format[<?php echo $format->id; ?>]"
									id="<?php echo $id; ?>"
									<?php if ( in_array( $format->id, $this_show_formats ) ): ?>checked="checked"<?php endif; ?> />
								<?php echo $format->name; ?>
							</label>
							<br/>
						<?php endforeach; ?>
					</td>
				</tr>
			</table>
			<?php submit_button( $button_text ); ?>
		</form>
		<?php
	}
	
	private function edit_template() {
		$show = Podlove_Show::find_by_id( $_REQUEST[ 'show' ] );
		?>
		<h3><?php echo Podlove::t( 'Edit Show' ); ?>: <?php echo $show->name ?></h3>
		
		<?php $this->form_template( $show, 'save' ); ?>
		<?php
	}
	
}