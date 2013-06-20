<?php
namespace Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class FileTypes extends Tab {

	public function init() {
		$this->page_type = 'custom';
		add_action( 'podlove_expert_settings_page', array( $this, 'register_page' ) );
	}

	public function register_page() {
		$file_type = new \Podlove\Settings\FileType( 'podlove_settings_settings_handle' );
		$file_type->page();
	}
}