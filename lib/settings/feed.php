<?php
namespace Podlove\Settings;

class Feed {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		self::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Podcast Feeds',
			/* $menu_title */ 'Podcast Feeds',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_feeds_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}
	
	/**
	 * Process form: save/update a format
	 */
	private function save() {
		if ( ! isset( $_REQUEST['feed'] ) )
			return;
			
		$feed = \Podlove\Model\Feed::find_by_id( $_REQUEST['feed'] );
		$feed->update_attributes( $_POST['podlove_feed'] );
		
		$this->redirect( 'edit', $feed->id );
	}
	
	/**
	 * Process form: create a format
	 */
	private function create() {
		global $wpdb;
		
		$feed = new \Podlove\Model\Feed;
		$feed->update_attributes( $_POST['podlove_feed'] );

		$this->redirect( 'index' );
	}
	
	/**
	 * Process form: delete a format
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['feed'] ) )
			return;

		\Podlove\Model\Feed::find_by_id( $_REQUEST['feed'] )->delete();
		
		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $feed_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $feed_id ) ? '&feed=' . $feed_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}
	
	public function process_form() {

		if ( ! isset( $_REQUEST['feed'] ) )
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
			<h2><?php echo __( 'Feeds', 'podlove' ); ?> <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
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
		$feed = new \Podlove\Model\Feed;
		?>
		<h3><?php echo __( 'Add New Feed', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $feed, 'create', __( 'Add New Feed', 'podlove' ) );
	}
	
	private function view_template() {
		$table = new \Podlove\Feed_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function form_template( $feed, $action, $button_text = NULL ) {

		$form_args = array(
			'context' => 'podlove_feed',
			'hidden'  => array(
				'feed' => $feed->id,
				'action' => $action
			)
		);

		\Podlove\Form\build_for( $feed, $form_args, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

			$feed = $form->object;

			$episode_assets = \Podlove\Model\EpisodeAsset::all();
			$assets = array();
			foreach ( $episode_assets as $asset ) {
				$assets[ $asset->id ] = $asset->title;
			}	

			$wrapper->string( 'name', array(
				'label'       => __( 'Feed Title', 'podlove' ),
				'description' => __( 'Some podcast clients may display this title to describe the feed content.', 'podlove' ),
				'html' => array( 'class' => 'regular-text required' )
			) );

			$wrapper->checkbox( 'discoverable', array(
				'label'       => __( 'Discoverable?', 'podlove' ),
				'description' => __( 'Embed a meta tag into the head of your site so browsers and feed readers will find the link to the feed.', 'podlove' ),
				'default'     => true
			) );
			
			// TODO: update url
			$wrapper->string( 'slug', array(
				'label'       => __( 'Slug', 'podlove' ),
				'description' => ( $feed ) ? sprintf( __( 'Feed identifier. URL Preview: %s', 'podlove' ), '<span id="feed_subscribe_url_preview">' . $feed->get_subscribe_url() .  '</span>' ) : '',
				'html'        => array( 'class' => 'regular-text required' )
			) );

			$wrapper->radio( 'format', array(
				'label'   => __( 'Format', 'podlove' ),
				'options' => array( 'rss' => 'RSS', 'atom' => 'Atom' ),
				'default' => 'rss'
			) );
						
			$wrapper->select( 'episode_asset_id', array(
				'label'       => __( 'Episode Media File', 'podlove' ),
				'options'     => $assets,
				'html'        => array( 'class' => 'required' )
			) );
			
			$wrapper->string( 'itunes_feed_id', array(
				'label'       => __( 'iTunes Feed ID', 'podlove' ),
				'description' => __( 'Is used to generate a link to the iTunes directory.', 'podlove' ),
				'html'        => array( 'class' => 'regular-text' )
			) );
							
			// todo: add PING url; see feedburner doc
			$wrapper->string( 'redirect_url', array(
				'label'       => __( 'Redirect Url', 'podlove' ),
				'description' => __( 'e.g. Feedburner URL', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );

			$wrapper->select( 'redirect_http_status', array(
				'label'       => __( 'Redirect Method', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'options' => array(
					'0'   => 'Don\'t redirect', 
					'307' => 'Temporary Redirect (HTTP Status 307)',
					'301' => 'Permanent Redirect (HTTP Status 301)'
				),
				'default' => 0
			) );
			
			$wrapper->checkbox( 'enable', array(
				'label'       => __( 'Allow Submission to Directories', 'podlove' ),
				'description' => __( 'Allow this feed to appear in podcast directories.', 'podlove' ),
				'default'     => true
			) );
			
			// todo include summary?
			$wrapper->string( 'limit_items', array(
				'label'       => __( 'Limit Items', 'podlove' ),
				'description' => __( 'A feed only displays the most recent episodes. Define the amount. Leave empty to use the WordPress default.', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );
		} );
	}
	
	private function edit_template() {
		$feed = \Podlove\Model\Feed::find_by_id( $_REQUEST['feed'] );
		echo '<h3>' . sprintf( __( 'Edit Feed: %s', 'podlove' ), $feed->name ) . '</h3>';
		$this->form_template( $feed, 'save' );
	}
	
}