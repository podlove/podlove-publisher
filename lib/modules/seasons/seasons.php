<?php 
namespace Podlove\Modules\Seasons;

use \Podlove\Modules\Seasons\Model\Season;

class Seasons extends \Podlove\Modules\Base {

	protected $module_name = 'Seasons';
	protected $module_description = 'Group your episodes into seasons.';
	protected $module_group = 'metadata';

	public function load() {
		
		// module lifecycle
		add_action('podlove_module_was_activated_seasons', [$this, 'was_activated']);

		// register settings page
		add_action('podlove_register_settings_pages', function($handle) {
			new \Podlove\Modules\Seasons\Settings\Settings($handle);
		});

		add_filter( "set-screen-option", function($status, $option, $value) {
			if ($option == 'podlove_seasons_per_page')
				return $value;
			
			return $status;
		}, 10, 3 );

	}

	public function was_activated( $module_name ) {
		Season::build();
	}
}