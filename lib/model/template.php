<?php
namespace Podlove\Model;

use \Podlove\Model\Template;

/**
 * Episode Templates.
 */
class Template extends Base {

	public static function find_one_by_title_with_fallback($template_id) {
		if ( $template = self::find_one_by_title($template_id) ) {
			return $template;
		}
		
		self::activate_network_scope();
		$global_template = self::find_one_by_title($template_id);

		return $global_template;
	}

}

Template::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Template::property( 'title', 'VARCHAR(255)' );
Template::property( 'content', 'TEXT' );
