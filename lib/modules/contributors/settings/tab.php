<?php
namespace Podlove\Modules\Contributors\Settings;
use \Podlove\Settings\Settings;

/**
 * Represents one Expert Settings Tab
 */
class Tab extends \Podlove\Settings\Podcast\Tab {

	protected $page_hook = 'edit.php?post_type=podcast&page=podlove_contributors_settings_handle';

	public function get_url() {
		return sprintf( "edit.php?post_type=podcast&page=%s&podlove_tab=%s&action=%s&contributor=%d", 
				$_REQUEST['page'], 
				$this->get_slug(),
				$_REQUEST['action'],
				$_REQUEST['contributor']
			);
	}

	public function init() {
		add_action( $this->page_hook, array( $this, 'register_page' ) );
	}

	public function register_page() {
		$contributor = \Podlove\Modules\Contributors\Model\Contributor::find_by_id( $_REQUEST['contributor'] );

		switch ( $_GET["action"] ) {
			case 'new':   $action = 'create';  break;
			case 'edit':  $action = 'save'; break;
			default:      $action = 'delete'; break;
		}
		
		$form_attributes = array(
			'context' => 'podlove_contributor',
			'action' => 'edit.php?post_type=podcast&page=podlove_contributors_settings_handle',
			'hidden'  => array(
				'contributor' => $contributor->id,
				'action' => $action,
				'podlove_tab' => $_GET['podlove_tab']
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

		$this->form_template($form_attributes, $action, $contributor);
	}

}