<?php
namespace Podlove\Modules\Flattr;

class Flattr extends \Podlove\Modules\Base {

	protected $module_name = 'Flattr';
	protected $module_description = 'Enable support for <a href="https://flattr.com/" target="_blank">Flattr</a>.';
	protected $module_group = 'web publishing';

	public function load() {
		add_action('wp_head', [__CLASS__, 'insert_script']);

		add_action('podlove_podcast_settings_tabs', [__CLASS__, 'podcast_settings_tabs']);
		add_filter('podlove_templates_global_context', [__CLASS__, 'add_flattr_to_template_context']);

		FeedExtension::init();
		ContributorExtension::init();
	}


	/**
	 * Get Flattr setting by name.
	 * 
	 * If no name is given, return all settings.
	 * 
	 * Settings: account, contributor_shortcode_default
	 * 
	 * @param  string|null $name setting name
	 * @return mixed             setting value
	 */
	public static function get_setting($name = null) {
		$defaults = [
			'account'                       => '',
			'contributor_shortcode_default' => 'yes'
		];

		$options = get_option('podlove_flattr', []);
		$options = wp_parse_args($options, $defaults);

		return is_null($name) ? $options : $options[$name];
	}

	public static function insert_script() {
		\Podlove\load_template('lib/modules/flattr/views/flattr_script');
	}

	public static function podcast_settings_tabs($tabs) {
		$tabs->addTab(new PodcastFlattrSettingsTab(__('Flattr', 'podlove')));
		return $tabs;
	}

	public static function add_flattr_to_template_context($context) {
		$context['flattr'] = new Template\Flattr;
		return $context;
	}
}
