<?php
namespace Podlove\Settings;

/**
 * @todo The structure of this class differs from Format and Show.
 * That is because it is nested into Shows, not a standalone site.
 * It would be great to find a way to unify both structures.
 * You know, like Rails nested forms. But that might be overly complex.
 */
class Feed {
	
	protected $field_keys;
	protected $show;
	protected $feed;
	
	public function __construct( $hook, $feed ) {
		
		$raw_formats = \Podlove\Model\Format::all();
		$formats = array();
		foreach ( $raw_formats as $format ) {
			$formats[ $format->id ] = $format->name;
		}
		
		$this->field_keys = array(
			'name' => array(
				'label'       => \Podlove\t( 'Name' ),
				'description' => \Podlove\t( '' )
			),
			'title' => array(
				'label'       => \Podlove\t( 'Title' ),
				'description' => \Podlove\t( '' )
			),
			'slug' => array(
				'label'       => \Podlove\t( 'Slug' ),
				'description' => \Podlove\t( '' )
			),
			'format_id' => array(
				'label'       => \Podlove\t( 'File Format' ),
				'description' => \Podlove\t( '' ),
				'args' => array(
					'type'     => 'select',
					'options'  => $formats
				)
			),
			'redirect_url' => array(
				'label'       => \Podlove\t( 'Redirect Url' ),
				'description' => \Podlove\t( 'e.g. Feedburner URL' )
			),
			'block' => array(
				'label'       => \Podlove\t( 'Block feed' ),
				'description' => \Podlove\t( 'itunes:block' ),
				'args' => array(
					'type'     => 'checkbox'
				)
			),
			'discoverable' => array(
				'label'       => \Podlove\t( 'Discoverable' ),
				'description' => \Podlove\t( '' ),
				'args' => array(
					'type'     => 'checkbox'
				)
			),
			'limit_items' => array(
				'label'       => \Podlove\t( 'Limit Items' ),
				'description' => \Podlove\t( '' )
			),
			'show_description' => array(
				'label'       => \Podlove\t( 'Show Description' ),
				'description' => \Podlove\t( 'You may want to hide the episode subscriptions to reduce file size.' ),
				'args' => array(
					'type'     => 'checkbox'
				)
			)
		);
		
		add_action( 'admin_init', array( $this, 'process_form' ) );
		
		$this->feed = $feed;
		
		if ( isset( $_REQUEST[ 'show' ] ) ) {
			$show_id = (int) $_REQUEST[ 'show' ];
			$this->show = \Podlove\Model\Show::find_by_id( $show_id );
			add_meta_box(
				/* $id            */ 'podlove_show_feeds_' . $feed->id,
				/* $title         */ sprintf( '%s: %s', \Podlove\t( 'Feed' ), $feed->name ),
				/* $callback      */ array( $this, 'feed_box' ),
				/* $page          */ $hook,
				/* $context       */ 'normal',
				/* $priority      */ 'default'
				/* $callback_args */ 
			);
		}
	}
	
	/**
	 * Process form: save/update a show
	 */
	private function save() {
		$show_id = ( isset( $_REQUEST[ 'show' ] ) ) ? (int) $_REQUEST[ 'show' ] : NULL;
		$feed_id = ( isset( $_REQUEST[ 'feed' ] ) ) ? (int) $_REQUEST[ 'feed' ] : NULL;
		
		$feed = \Podlove\Model\Feed::find_by_id( $feed_id );
		
		if ( ! $show_id || ! $feed_id || $feed_id != $this->feed->id  )
			return;
			
		if ( ! isset( $_POST[ 'podlove_feed' ] ) || ! is_array( $_POST[ 'podlove_feed' ] ) )
			return;
			
		// @fixme: checkbox stuff should not happen here. ideally somewhere in the builder context, maybe?
		foreach ( $this->field_keys as $key => $field_values ) {
			$value = isset( $_POST[ 'podlove_feed' ][ $key ] ) ? $_POST[ 'podlove_feed' ][ $key ] : NULL;
			if ( isset( $field_values[ 'args' ] ) && $field_values[ 'args' ][ 'type' ] == 'checkbox' ) {
				$feed->{$key} = ( $value == 'on' );
			} else {
				$feed->{$key} = $value;
			}	
		}
		
		$feed->save();
		
		$this->redirect( 'edit', $show_id );
	}
	
	/**
	 * Process form: create new show
	 */
	private function create() {
	}
	
	/**
	 * Process form: delete a show
	 */
	private function delete() {
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
		if ( $action == 'save' ) {
			$this->save();
		}
	}
	
	public function page() {
	}
	
	private function new_template() {
	}
	
	private function view_template() {

	}
	
	public function feed_box( $_ ) {
		?>
		<form action="<?php echo admin_url( 'admin.php?page=' . $_REQUEST[ 'page' ] ) ?>" method="post">
			<input type="hidden" name="show" value="<?php echo $this->show->id ?>" />
			<input type="hidden" name="feed" value="<?php echo $this->feed->id ?>" />
			<input type="hidden" name="action" value="save" />
			<table class="form-table">
				<?php foreach ( $this->field_keys as $key => $value ): ?>
					<?php \Podlove\Form\input( 'podlove_feed', $this->feed, $key, $value ); ?>
				<?php endforeach; ?>
			</table>
			
			<?php submit_button(); ?>
			<br class="clear" />
		</form>
		<?php
	}
	
	private function form_template( $show, $action, $button_text = NULL ) {

	}
	
	private function edit_template() {

	}
	
}
