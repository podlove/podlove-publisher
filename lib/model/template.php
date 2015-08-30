<?php
namespace Podlove\Model;

use \Podlove\Model\Template;

/**
 * Episode Templates.
 */
class Template extends Base {

	use NetworkTrait;

	/**
	 * Returns all local + network templates.
	 * 
	 * Local template override global templates with same title.
	 * 
	 * @return array
	 */
	public static function all_globally() {
		
		if (!is_multisite())
			return Template::all();

		$local  = Template::all();
		$global = Template::with_network_scope(function() { return Template::all(); });

		$all = [];
		
		foreach ($global as $template) {
			$all[$template->title] = $template;
		}

		foreach ($local as $template) {
			$all[$template->title] = $template;
		}

		ksort($all);

		return array_values($all);
	}
	
	public static function find_one_by_title_with_fallback($template_id) {
		if ( $template = self::find_one_by_title($template_id) ) {
			return $template;
		}
		
		if (is_multisite()) {
			return self::with_network_scope(function() use ($template_id) {
				return self::find_one_by_title($template_id);
			});
		}

		return null;
	}

}

Template::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Template::property( 'title', 'VARCHAR(255)' );
Template::property( 'content', 'TEXT' );
