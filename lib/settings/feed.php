<?php
namespace Podlove\Settings;

class Feed {
	
	protected $field_keys;
	protected $show;
	protected $feed;
	
	public function __construct() {
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}
	
	/**
	 * Process form: create new show
	 */
	private function create() {
		$show_id = ( isset( $_REQUEST[ 'show' ] ) ) ? (int) $_REQUEST[ 'show' ] : NULL;

		if ( ! $show_id )
			return;
			
		$feed = new \Podlove\Model\Feed;
		$feed->show_id = $show_id;
		$feed->save();
		$this->redirect( 'edit', $show_id );
	}
	
	/**
	 * Process form: delete a show
	 */
	private function delete() {
		$show_id = ( isset( $_REQUEST[ 'show' ] ) ) ? (int) $_REQUEST[ 'show' ] : NULL;
		$feed_id = ( isset( $_REQUEST[ 'feed' ] ) ) ? (int) $_REQUEST[ 'feed' ] : NULL;
		
		$feeds = \Podlove\Model\Feed::find_all_by_show_id( $show_id );
		
		foreach ( $feeds as $feed ) {
			if ( $feed->id == $feed_id ) {
				$feed->delete();
			}
		}
			
		$this->redirect( 'edit', $show_id );
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
		} elseif ( $action == 'delete' ) {
			$this->delete();
		} elseif ( $action == 'create' ) {
			$this->create();
		}
	}
	
}
