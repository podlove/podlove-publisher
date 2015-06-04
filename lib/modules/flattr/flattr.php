<?php
namespace Podlove\Modules\Flattr;

class Flattr extends \Podlove\Modules\Base {

	protected $module_name = 'Flattr';
	protected $module_description = 'Enable support for <a href="https://flattr.com/" target="_blank">Flattr</a>.';
	protected $module_group = 'web publishing';

	public function load() {
		add_action('wp_head', [__CLASS__, 'insert_script']);

		FeedExtension::init();
		ContributorExtension::init();
	}

	public static function insert_script() {
		\Podlove\load_template('lib/modules/flattr/views/flattr_script');
	}
}
