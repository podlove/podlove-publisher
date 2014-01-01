<?php
namespace Podlove\Template;

abstract class Wrapper {

	/**
	 * Dynamically add accessors to template wrappers.
	 *
	 * Example:
	 *
	 * Adding `{{ episode.summary }}` functionality
	 *
	 * ```
	 * \Podlove\Template\Episode::add_accessor(
	 * 	'summary',
	 * 	function($return, $method_name, $episode, $post) {
	 * 		return $episode->summary;
	 * 	}, 4
	 * );
	 * ```
	 * 
	 * @param string    $name            accessor name
	 * @param function  $method          accessor implementation
	 * @param integer   $extraFilterArgs filter arguments length, defaults to 2
	 */
	public static function add_accessor($name, $method, $extraFilterArgs = 2) {

		// let __isset return true on this accessor so Twig knows it exists
		add_filter(
			static::get_magic_isset_filter_name(),
			function($return, $method_name) use ($name) {
				return $method_name == $name ? true : $return;
		}, 10, 2);

		// implement the actual accessor
		add_filter(
			static::get_magic_getter_filter_name(),
			$method,
			10,
			$extraFilterArgs
		);

	}

	public function __get($name) {
		return apply_filters_ref_array(
			static::get_magic_getter_filter_name(),
			array_merge(array(null, $name), $this->getExtraFilterArgs()) 
		);
	}

	public function __isset($name) {
		return apply_filters_ref_array(
			static::get_magic_isset_filter_name(),
			array_merge(array(false, $name), $this->getExtraFilterArgs()) 
		);
	}

	public static function get_class_slug() {
		$class = get_called_class();
		$split = explode("\\", $class);
		return strtolower(end($split));
	}

	public static function get_magic_getter_filter_name() {
		return 'podlove_template_' . static::get_class_slug() . '_method';
	}

	public static function get_magic_isset_filter_name() {
		return 'podlove_template_' . static::get_class_slug() . '_method_isset';
	}

	/**
	 * Override to pass extra arguments to filter methods.
	 *
	 * Adds arguments to the following filters:
	 * 	- podlove_template_<wrapper>_method
	 * 	- podlove_template_<wrapper>_method_isset
	 * 
	 * @return array
	 */
	protected function getExtraFilterArgs() {
		return array();
	}
}