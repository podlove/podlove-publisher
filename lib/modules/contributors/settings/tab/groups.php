<?php 
namespace Podlove\Modules\Contributors\Settings\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class Groups extends Tab {

	public function init() {
		$this->page_type = 'custom';
		add_action( 'podlove_expert_settings_page', array( $this, 'register_page' ) );
	}

	public function register_page() {
		$this->object = $this->getObject();
		$this->object->page();
	}

	public function getObject() {
		return new \Podlove\Modules\Contributors\Settings\ContributorGroups( 'podlove_contributor_settings' );
	}

}