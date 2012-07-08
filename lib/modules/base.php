<?php 
namespace Podlove\Modules;

abstract class Base {

	abstract function load();

	/**
	 * Fetch internal module names by iterating over module directories.
	 * 
	 * @return array
	 */
	public static function get_all_module_names() {
		$modules_dir = \Podlove\PLUGIN_DIR . 'lib/modules/';
		$modules = array();

		if ( $dhandle = opendir( $modules_dir ) ) {
			while (false !== ( $fname = readdir( $dhandle ) ) ) {
				if ( ( $fname != '.') && ( $fname != '..' ) && is_dir( $modules_dir . $fname ) ) {
					$modules[] = $fname;
				}
			}
			closedir( $dhandle );
		}

		return $modules;
	}

	/**
	 * Get full class name for the main module class.
	 * 
	 * @param  string $module_name
	 * @return string              
	 */
	public static function get_class_by_module_name( $module_name ) {
		$class_name     = podlove_snakecase_to_camelsnakecase( $module_name );
		$namespace_name = podlove_camelsnakecase_to_camelcase( $class_name );

		return "\Podlove\Modules\\$namespace_name\\$class_name";
	}

	/**
	 * Return public module name.
	 * 
	 * @return string
	 */
	function get_module_name() {
		return $this->module_name;
	}

	/**
	 * Return public module description.
	 * 
	 * @return string
	 */
	function get_module_description() {
		return $this->module_description;
	}

}