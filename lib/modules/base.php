<?php 
namespace Podlove\Modules;

abstract class Base {

	/**
	 * Stores information about module options.
	 * 
	 * @var array
	 */
	protected $options = array();

	/**
	 * All Modules are singletons.
	 */
	static public function instance() {
		static $instances = array();

		 $calledClass = get_called_class();

		 if ( ! isset($instances[ $calledClass ] ) )
		     $instances[ $calledClass ] = new $calledClass();

		 return $instances[$calledClass];
	}

	protected function __construct(){}
	final private function __clone(){}

	/**
	 * This will be called to load the module.
	 * 
	 * Here hooks can be registered, files be loaded etc.
	 * The module must not change any behavior before load() being called!
	 */
	abstract function load();

	/**
	 * Fetch module names by iterating over module directories.
	 * 
	 * @return array
	 */
	public static function get_all_module_names() {
		$modules_dir = \Podlove\PLUGIN_DIR . 'lib/modules/';
		$modules = array();

		if ( $dhandle = opendir( $modules_dir ) ) {
			while (false !== ( $fname = readdir( $dhandle ) ) ) {
				if ( ( $fname != '.' ) && ( $fname != '..' ) && is_dir( $modules_dir . $fname ) ) {
					$modules[] = $fname;
				}
			}
			closedir( $dhandle );
		}

		return $modules;
	}

	/**
	 * Fetch module names for active modules only.
	 * 
	 * @return array
	 */
	public static function get_active_module_names() {
		$modules = self::get_all_module_names();

		return array_merge(
			array_filter($modules, function ($module) {
				return Base::is_active($module);
			}),
			self::get_core_module_names()
		);
	}

	/**
	 * Fetch module names for core modules only.
	 * 
	 * @return array
	 */
	public static function get_core_module_names() {
		return array_filter(self::get_all_module_names(), function($module) {
			$class = self::get_class_by_module_name($module);
			return $class::is_core();
		});
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

	/**
	 * Is this module core functionality?
	 * 
	 * Core modules are always-on and don't appear in modules list.
	 * 
	 * @return boolean
	 */
	public static function is_core() {
		return false;
	}

	public function get_module_url() {
		return \Podlove\PLUGIN_URL . '/lib/modules/' . $this->get_module_directory_name();
	}

	protected function get_module_class_name() {
		$fq_class_name = get_class($this);
		return substr( $fq_class_name , strrpos( $fq_class_name, '\\')+1);
	}

	protected function get_module_namespace_name() {
		return podlove_camelsnakecase_to_camelcase( $this->get_module_class_name() );
	}

	protected function get_module_directory_name() {
		return strtolower( $this->get_module_class_name() );
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
	public function get_module_name() {
		return $this->module_name;
	}

	/**
	 * Return public module description.
	 * 
	 * @return string
	 */
	public function get_module_description() {
		return $this->module_description;
	}

	/**
	 * Return public module group key.
	 * 
	 * @return string
	 */
	public function get_module_group() {
		return isset( $this->module_group ) ? $this->module_group : "";
	}

	/**
	 * Return option name of the field where module options are stored.
	 * 
	 * @return string
	 */
	public function get_module_options_name() {
		return 'podlove_module_' .  $this->get_module_directory_name();
	}

	/**
	 * Return field of all module options.
	 * 
	 * @return array
	 */
	public function get_module_options() {
		return get_option( $this->get_module_options_name(), array() );
	}

	/**
	 * Return value for a single module option.
	 * 
	 * @param  string $name
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get_module_option( $name, $default = NULL ) {
		$options = $this->get_module_options();
		return isset( $options[$name] ) ? $options[$name] : $default;
	}

	/**
	 * Set value for a single module option.
	 * 
	 * @param string $name
	 * @param mixed  $value 
	 */
	public function update_module_option( $name, $value ) {
		$options = $this->get_module_options();
		$options[$name] = $value;
		update_option( $this->get_module_options_name(), $options );
	}

	public function register_option( $name, $input_type, $args ) {
		$this->options[$name] = array(
			'input_type' => $input_type,
			'args'       => $args
		);
	}

	public function get_registered_options() {
		return $this->options;
	}

	public static function is_module_settings_page() {
		// If I'm on the module page ...
		if (filter_input(INPUT_GET, 'page') == 'podlove_settings_modules_handle')
			return true;

		// ... or saving on the module page
		if (stripos(filter_input(INPUT_SERVER, 'REQUEST_URI'), 'options.php') !== FALSE)
			return true;

		return false;
	}

}