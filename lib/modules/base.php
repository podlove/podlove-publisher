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
	 * Fetch internal module names for active modules only.
	 * 
	 * @return array
	 */
	public static function get_active_module_names() {
		$modules = self::get_all_module_names();

		return array_filter( $modules, function ( $module ) {
			return Base::is_active( $module );
		} );
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

	public static function is_active( $module_name ) {
		$options = get_option( 'podlove_active_modules' );
		return isset( $options[ $module_name ] );
	}

	public static function activate( $module_name ) {
		$options = get_option( 'podlove_active_modules' );
		if ( ! isset( $options[ $module_name ] ) ) {
			$options[ $module_name ] = 'on';
			update_option( 'podlove_active_modules', $options );
		}
	}

	protected function get_module_url() {
		return \Podlove\PLUGIN_URL . '/lib/modules/' . $this->get_module_directory_name();
	}

	protected function get_module_class_name() {
		return podlove_snakecase_to_camelsnakecase( $this->module_name );
	}

	protected function get_module_namespace_name() {
		return podlove_camelsnakecase_to_camelcase( $this->get_module_class_name() );
	}

	protected function get_module_directory_name() {
		return strtolower( str_replace( ' ', '_', $this->module_name ) );
	}
	
	public static function deactivate( $module_name ) {
		$options = get_option( 'podlove_active_modules' );
		if ( isset( $options[ $module_name ] ) ) {
			unset( $options[ $module_name ] );
			update_option( 'podlove_active_modules', $options );
		}
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