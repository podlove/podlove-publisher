<?php
namespace Podlove\Modules\Migration\Settings\Wizard;

abstract class Step {
	// public abstract function template() {}	

	public static function get_page_link( $step = 1 ) {
		return sprintf( '?page=%s&step=%s', 'podlove_settings_migration_handle', $step );
	}
}