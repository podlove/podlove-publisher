<?php
namespace Podlove\Model;

trait NetworkTrait {

	/**
	 * Enables or disables network scope
	 */
	public static $is_network = false;

	public static function table_name() {
		global $wpdb;
		
		if ( static::$is_network )
			return $wpdb->base_prefix . 'global_' . parent::name();

		return parent::table_name();
	}

	/**
	 * Activate network scope.
	 * 
	 * @todo  move into a trait
	 */	
	public static function activate_network_scope() {
		static::$is_network = true;
	}

	/**
	 * Deactivate network scope.
	 */
	public static function deactivate_network_scope() {
		static::$is_network = false;
	}

	/**
	 * Execute a callback within network scope.
	 * 
	 * Example:
	 * 
	 * 		$templates = Template::with_network_scope(function(){
	 *			return Template::all();
	 *		});
	 * 
	 * @param  callable $callback
	 * @return mixed    Returns result of evaluated callback.
	 */
	public static function with_network_scope($callback) {

		if (!is_callable($callback))
			throw new \InvalidArgumentException('expected argument 1 of ::with_network_scope to be callable');

		self::activate_network_scope();
		$result = $callback();
		self::deactivate_network_scope();

		return $result;
	}
}