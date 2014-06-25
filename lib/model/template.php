<?php
namespace Podlove\Model;

/**
 * Episode Templates.
 */
class Template extends Base {

	public static function find_one_by_title( $title ) {
		if ( ! $template = self::fetch_template_by_title( $title ) ) {
			if ( \Podlove\Modules\Base::is_active('networks') ) {
				switch_to_blog ( 1 );
				if ( ! $template = \Podlove\Modules\Networks\Model\Template::fetch_template_by_title( $title ) ) {
					restore_current_blog();
					return NULL;
				}
			}
		}
		restore_current_blog();
		return $template;
	}

	public static function fetch_template_by_title( $title ) {
		global $wpdb;
		
		$row = $wpdb->get_row(
			'SELECT * FROM ' . self::table_name() . ' WHERE `title` = \'' . $title . '\' LIMIT 0,1'
		);
		
		if ( ! $row ) {
			return NULL;
		}
		
		foreach ( $row as $property => $value ) {
			$model->$property = $value;
		}
		
		return $model;
	}

}

Template::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Template::property( 'title', 'VARCHAR(255)' );
Template::property( 'content', 'TEXT' );
