<?php
namespace Podlove\Settings;

class Show {
	
	protected $field_keys;
	protected $pagehook;
	
	public function __construct( $handle ) {

		$this->field_keys = array(
			'name' => array(
				'label'       => \Podlove\t( 'Name' ),
				'description' => \Podlove\t( '' )
			),
			'slug' => array(
				'label'       => \Podlove\t( 'Slug' ),
				'description' => \Podlove\t( '' )
			),
			'subtitle' => array(
				'label'       => \Podlove\t( 'Show Subtitle' ),
				'description' => \Podlove\t( 'The subtitle is used by iTunes.' )
			),
			'cover_image' => array(
				'label'       => \Podlove\t( 'Cover Image' ),
				'description' => \Podlove\t( 'itunes:image (Cover Image URL, 600x600px)' )
			),
			'summary' => array(
				'label'       => \Podlove\t( 'Summary' ),
				'description' => \Podlove\t( 'itunes:summary' ),
				'args' => array(
					'type'    => 'textarea'
				)
			),
			'author_name' => array(
				'label'       => \Podlove\t( 'Author Name' ),
				'description' => \Podlove\t( 'itunes:author (Artist name. Publicly displayed.)' )
			),
			'owner_name' => array(
				'label'       => \Podlove\t( 'Owner Name' ),
				'description' => \Podlove\t( 'itunes:owner > itunes:name (Used by iTunes to contact you. Not publicly displayed.)' )
			),
			'owner_email' => array(
				'label'       => \Podlove\t( 'Owner Email' ),
				'description' => \Podlove\t( 'itunes:owner > itunes:email (Used by iTunes to contact you. Not publicly displayed.)' )
			),
			'keywords' => array(
				'label'       => \Podlove\t( 'Keywords' ),
				'description' => \Podlove\t( 'itunes:keywords (separate with commas)' )
			),
			'category_1' => array(
				'label'       => \Podlove\t( 'Categories' ),
				'description' => '',
				'args' => array(
					'type'     => 'select',
					'options'  => \Podlove\Itunes\categories()
				)
			),
			'category_2' => array(
				'label'       => '',
				'description' => '',
				'args' => array(
					'type'     => 'select',
					'options'  => \Podlove\Itunes\categories()
				)
			),
			'category_3' => array(
				'label'       => '',
				'description' => '',
				'args' => array(
					'type'     => 'select',
					'options'  => \Podlove\Itunes\categories()
				)
			),
			'explicit' => array(
				'label'       => \Podlove\t( 'Explicit' ),
				'description' => \Podlove\t( 'itunes:explicit' ),
				'args' => array(
					'type'    => 'select',
					'options' => array(
						1 => \Podlove\t( 'yes' ),
						0 => \Podlove\t( 'no' )
					)
				)
			),
			'label' => array(
				'label'       => \Podlove\t( 'Show Label' ),
				'description' => \Podlove\t( 'The show label is the prefix for every show title. It should be all caps and 3 or 4 characters long. Example: POD' )
			),
			'episode_prefix' => array(
				'label'       => \Podlove\t( 'Episode Prefix' ),
				'description' => \Podlove\t( 'Slug for file URI. Example: pod_' )
			),
			'media_file_base_uri' => array(
				'label'       => \Podlove\t( 'Media File Base URI' ),
				'description' => \Podlove\t( 'Example: http://cdn.example.com/pod/' )
			),
			'uri_delimiter' => array(
				'label'       => \Podlove\t( 'URI Delimiter' ),
				'description' => \Podlove\t( 'Example: -' )
			),
			'episode_number_length' => array(
				'label'       => \Podlove\t( 'Episode Number Length' ),
				'description' => \Podlove\t( 'If the episode number has fewer digits than defined here, it will be prefixed with leading zeroes. Example: 3' )
			)
		);
		
		$this->pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Shows',
			/* $menu_title */ 'Shows',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_shows_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
		
		if ( isset( $_REQUEST[ 'show' ] ) ) {
			$show_id = (int) $_REQUEST[ 'show' ];
			$feeds = \Podlove\Model\Feed::find_all_by_show_id( $show_id );
			foreach ( $feeds as $feed ) {
				new \Podlove\Settings\Feed( $this->pagehook, $feed );
			}
			// init one feed so the hooks can be registered
			if ( count( $feeds ) === 0 )
				new \Podlove\Settings\Feed( $this->pagehook, NULL );
			
		}
	}
	
	/**
	 * Process form: save/update a show
	 */
	private function save() {
		if ( ! isset( $_REQUEST[ 'show' ] ) )
			return;
			
		$show = \Podlove\Model\Show::find_by_id( $_REQUEST[ 'show' ] );
		
		if ( ! isset( $_POST[ 'podlove_show' ] ) || ! is_array( $_POST[ 'podlove_show' ] ) )
			return;
			
		foreach ( $_POST[ 'podlove_show' ] as $key => $value ) {
			$show->{$key} = $value;
		}
		$show->save();
		
		$this->redirect( 'edit', $show->id );
	}
	
	/**
	 * Process form: create new show
	 */
	private function create() {
		global $wpdb;
		
		$show = new \Podlove\Model\Show;
		
		if ( ! isset( $_POST[ 'podlove_show' ] ) || ! is_array( $_POST[ 'podlove_show' ] ) )
			return;
			
		foreach ( $_POST[ 'podlove_show' ] as $key => $value ) {
			$show->{$key} = $value;
		}
		$show->save();
		
		$this->redirect( 'edit', $wpdb->insert_id );
	}
	
	/**
	 * Process form: delete a show
	 */
	private function delete() {
		if ( ! isset( $_REQUEST[ 'show' ] ) || isset( $_REQUEST[ 'feed' ] ) )
			return;
			
		$show = \Podlove\Model\Show::find_by_id( $_REQUEST[ 'show' ] );
		$show->delete();

		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $show_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST[ 'page' ];
		$show   = ( $show_id ) ? '&show=' . $show_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}
	
	public function process_form() {
		$action = ( isset( $_REQUEST[ 'action' ] ) ) ? $_REQUEST[ 'action' ] : NULL;
		
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
			<h2>Podlove Shows <a href="?page=<?php echo $_REQUEST[ 'page' ]; ?>&amp;action=new" class="add-new-h2"><?php echo \Podlove\t( 'Add New' ); ?></a></h2>
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
		$show = new \Podlove\Model\Show;
		?>
		<h3><?php echo \Podlove\t( 'Add New Show' ); ?></h3>
		<?php
		$this->form_template( $show, 'create', \Podlove\t( 'Add New Show' ) );
	}
	
	private function view_template() {
		$table = new \Podlove\Show_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function form_template( $show, $action, $button_text = NULL ) {
		?>
		<form action="<?php echo admin_url( 'admin.php?page=' . $_REQUEST[ 'page' ] ) ?>" method="post">
			<input type="hidden" name="show" value="<?php echo $show->id ?>" />
			<input type="hidden" name="action" value="<?php echo $action; ?>" />
			<table class="form-table">
				<?php foreach ( $this->field_keys as $key => $value ): ?>
					<?php \Podlove\Form\input( 'podlove_show', $show, $key, $value ); ?>
				<?php endforeach; ?>
			</table>
			
			<?php submit_button( $button_text ); ?>
		</form>
		
		<h3><?php echo \Podlove\t( 'Feeds for this Show' ); ?></h3>
		
		<div class="metabox-holder">
			<?php
			if ( is_multisite() && is_plugin_active_for_network( plugin_basename( PLUGIN_FILE ) ) )
				$options = get_site_option( $_REQUEST[ 'page' ] );
			else
				$options = get_option( $_REQUEST[ 'page' ] );

			do_meta_boxes( $this->pagehook, 'normal', $options );
			do_meta_boxes( $this->pagehook, 'additional', $options );
			?>
		</div>
		
		<a href="?page=<?php echo $_REQUEST[ 'page' ]; ?>&amp;action=create&amp;show=<?php echo $show->id; ?>" class="button-primary">
			<?php echo \Podlove\t( 'Create New Feed' ); ?>
		</a>
			
		<?php
	}
	
	private function edit_template() {
		$show = \Podlove\Model\Show::find_by_id( $_REQUEST[ 'show' ] );
		?>
		<h3><?php echo \Podlove\t( 'Edit Show' ); ?>: <?php echo $show->name ?></h3>
		
		<?php $this->form_template( $show, 'save' ); ?>
		<?php
	}
	
}
