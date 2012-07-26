<?php 
namespace Podlove\Settings;

class MediaLocation {
	
	public function __construct() {
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	/**
	 * Process form: create new media_location
	 */
	private function create() {
		$show_id = ( isset( $_REQUEST['show'] ) ) ? (int) $_REQUEST['show'] : NULL;

		if ( ! $show_id )
			return;
			
		$media_location = new \Podlove\Model\MediaLocation;
		$media_location->show_id = $show_id;
		$media_location->save();
		$this->redirect( 'edit', $show_id );
	}
	
	/**
	 * Process form: delete a media_location
	 */
	private function delete() {
		$show_id           = ( isset( $_REQUEST['show'] ) ) ? (int) $_REQUEST['show'] : NULL;
		$media_location_id = ( isset( $_REQUEST['media_location'] ) ) ? (int) $_REQUEST['media_location'] : NULL;
		
		$media_locations = \Podlove\Model\MediaLocation::find_all_by_id( $media_location_id );
		
		foreach ( $media_locations as $media_location ) {
			if ( $media_location->id == $media_location_id ) {
				$media_location->delete();
			}
		}
			
		$this->redirect( 'edit', $show_id );
	}

	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $show_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $show_id ) ? '&show=' . $show_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}	

	public function process_form() {
		$action  = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;
		$subject = ( isset( $_REQUEST['subject'] ) ) ? $_REQUEST['subject'] : NULL;

		if ( $subject != 'media_location' )
			return;

		if ( $action == 'delete' ) {
			$this->delete();
		} elseif ( $action == 'create' ) {
			$this->create();
		}
	}

}