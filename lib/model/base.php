<?php
namespace Podlove\Model;

abstract class Base
{
	/**
	 * Property dictionary for all tables
	 * @todo refactor into properties for current table only via late static binding
	 */
	private static $properties = array();
	
	private $is_new = true;
	
	/**
	 * Contains property values
	 */
	private $data = array();
	
	public function __set( $name, $value ) {
		if ( self::has_property( $name ) ) {
			$this->set_property( $name, $value );
		} else {
			$this->$name = $value;
		}
	}
	
	private function set_property( $name, $value ) {
		$this->data[ $name ] = $value;
	}
	
	public function __get( $name ) {
		if ( self::has_property( $name ) ) {
			return $this->get_property( $name );
		} elseif ( property_exists( $this, $name ) ) {
			return $this->$name;
		} else {
			return NULL;
		}
	}
	
	private function get_property( $name ) {
		if ( isset( $this->data[ $name ] ) ) {
			return $this->data[ $name ];
		} else {
			return NULL;
		}
	}
	
	/**
	 * Define a property with name and type.
	 * 
	 * Currently only supports basics.
	 * @todo enable additional options like NOT NULL, DEFAULT etc.
	 * 
	 * @param string $name Name of the property / column
	 * @param string $type mySQL column type 
	 */
	public static function property( $name, $type, $args = array() ) {
		$class = get_called_class();
		
		if ( ! isset( self::$properties[ $class ] ) ) {
			self::$properties[ $class ] = array();
		}

		// "id" columns and those ending on "_id" get an index by default
		$index = $name == 'id' || stripos( $name, '_id' );
		// but if the argument is set, it overrides the default
		if (isset($args['index'])) {
			$index = $args['index'];
		}
		
		self::$properties[ $class ][] = array(
			'name'  => $name,
			'type'  => $type,
			'index' => $index,
			'index_length' => isset($args['index_length']) ? $args['index_length'] : null,
			'unique' => isset($args['unique']) ? $args['unique'] : null
		);
	}
	
	/**
	 * Return a list of property dictionaries.
	 * 
	 * @return array property list
	 */
	private static function properties() {
		$class = get_called_class();
		
		if ( ! isset( self::$properties[ $class ] ) ) {
			self::$properties[ $class ] = array();
		}
		
		return self::$properties[ $class ];
	}
	
	/**
	 * Does the given property exist?
	 * 
	 * @param string $name name of the property to test
	 * @return bool True if the property exists, else false.
	 */
	public static function has_property( $name ) {
		return in_array( $name, self::property_names() );
	}
	
	/**
	 * Return a list of property names.
	 * 
	 * @return array property names
	 */
	public static function property_names() {
		return array_map( function ( $p ) { return $p['name']; } , self::properties() );
	}
	
	/**
	 * Does the table have any entries?
	 * 
	 * @return bool True if there is at least one entry, else false.
	 */
	public static function has_entries() {
		return self::count() > 0;
	}
	
	/**
	 * Return number of rows in the table.
	 * 
	 * @return int number of rows
	 */
	public static function count() {
		global $wpdb;
		
		$sql = 'SELECT COUNT(*) FROM ' . static::table_name();
		return (int) $wpdb->get_var( $sql );
	}

	public static function find_by_id( $id ) {
		return self::find_one_by_sql(
			'SELECT * FROM ' . static::table_name() . ' WHERE id = ' . (int) $id
		);
	}

	public static function find_all_by_property( $property, $value ) {
		return self::find_all_by_sql(
			'SELECT * FROM ' . static::table_name() . ' WHERE ' . $property .  ' = \'' . esc_sql($value) . '\''
		);
	}

	public static function find_one_by_property( $property, $value ) {
		return self::find_one_by_sql(
			'SELECT * FROM ' . static::table_name() . ' WHERE ' . $property .  ' = \'' . esc_sql($value) . '\' LIMIT 0,1'
		);
	}

	public static function find_all_by_where( $where ) {
		return self::find_all_by_sql(
			'SELECT * FROM ' . static::table_name() . ' WHERE ' . $where
		);
	}
	
	public static function find_one_by_where( $where ) {
		return self::find_one_by_sql(
			'SELECT * FROM ' . static::table_name() . ' WHERE ' . $where . ' LIMIT 0,1'
		);
	}

	// mimic ::find_one_by_<property>
	// mimic ::find_all_by_<property>
	public static function __callStatic( $name, $arguments ) {
		
		$property = preg_replace_callback(
			"/^find_one_by_(\w+)$/",
			function ( $p ) { return $p[ 1 ]; },
			$name
		);
		
		if ( $property !== $name ) {
			return self::find_one_by_property( $property, $arguments[ 0 ] );
		}
		
		$property = preg_replace_callback(
			"/^find_all_by_(\w+)$/",
			function ( $p ) { return $p[ 1 ]; },
			$name
		);
		
		if ( $property !== $name ) {
			return self::find_all_by_property( $property, $arguments[ 0 ] );
		}
		
		throw new \Exception("Fatal Error: Call to unknown static method $name.");
	}

	/**
	 * Retrieve first item from the table.
	 * 
	 * @return model object
	 */
	public static function first() {
		return self::find_one_by_sql(
			'SELECT * FROM ' . static::table_name() . ' LIMIT 0,1'
		);
	}
	
	public static function last() {
		return self::find_one_by_sql(
			'SELECT * FROM ' . static::table_name() . ' ORDER BY id DESC LIMIT 0,1'
		);
	}

	/**
	 * Retrieve all entries from the table.
	 *
	 * @param  string $sql_suffix optional SQL, appended after FROM clause
	 * @return array list of model objects
	 */
	public static function all( $sql_suffix = '' ) {
		return self::find_all_by_sql(
			'SELECT * FROM ' . static::table_name() . ' ' . $sql_suffix
		);
	}
	
	/**
	 * True if not yet saved to database. Else false.
	 */
	public function is_new() {
		return $this->is_new;
	}
	
	public function flag_as_not_new() {
		$this->is_new = false;
	}

	/**
	 * Rails-ish update_attributes for easy form handling.
	 *
	 * Takes an array of form values and takes care of serializing it.
	 * 
	 * @param  array $attributes
	 * @return bool
	 */
	public function update_attributes( $attributes ) {

		if ( ! is_array( $attributes ) )
			return false;
			
		foreach ( $attributes as $key => $value )
			$this->{$key} = $value;
		
		if ( isset( $_REQUEST['checkboxes'] ) && is_array( $_REQUEST['checkboxes'] ) ) {
			foreach ( $_REQUEST['checkboxes'] as $checkbox ) {
				if ( isset( $attributes[ $checkbox ] ) && $attributes[ $checkbox ] === 'on' ) {
					$this->$checkbox = 1;
				} else {
					$this->$checkbox = 0;
				}
			}
		}

		return $this->save();
	}

	/**
	 * Update and save a single attribute.
	 * 	
	 * @param  string $attribute attribute name
	 * @param  mixed  $value
	 * @return (bool) query success
	 */
	public function update_attribute($attribute, $value) {
		global $wpdb;

		$this->$attribute = $value;

		$sql = sprintf(
			"UPDATE %s SET %s = '%s' WHERE id = %s",
			static::table_name(),
			$attribute,
			$value,
			$this->id
		);

		return $wpdb->query( $sql );
	}
	
	/**
	 * Saves changes to database.
	 * 
	 * @todo use wpdb::insert()
	 */
	public function save() {
		global $wpdb;

		if ( $this->is_new() ) {

			$this->set_defaults();

			$sql = 'INSERT INTO '
			     . static::table_name()
			     . ' ( '
			     . implode( ',', self::property_names() )
			     . ' ) '
			     . 'VALUES'
			     . ' ( '
			     . implode( ',', array_map( array( $this, 'property_name_to_sql_value' ), self::property_names() ) )
			     . ' );'
			;
			$success = $wpdb->query( $sql );
			if ( $success ) {
				$this->id = $wpdb->insert_id;
			}
		} else {
			$sql = 'UPDATE ' . static::table_name()
			     . ' SET '
			     . implode( ',', array_map( array( $this, 'property_name_to_sql_update_statement' ), self::property_names() ) )
			     . ' WHERE id = ' . $this->id
			;
			$success = $wpdb->query( $sql );
		}

		$this->is_new = false;

		do_action('podlove_model_save', $this);
		do_action('podlove_model_change', $this);

		return $success;
	}

	/**
	 * Sets default values.
	 * 
	 * @return array
	 */
	private function set_defaults() {
		
		$defaults = $this->default_values();

		if ( ! is_array( $defaults ) || empty( $defaults ) )
			return;

		foreach ( $defaults as $property => $value ) {
			if ( $this->$property === NULL )
				$this->$property = $value;
		}

	}

	/**
	 * Return default values for properties.
	 * 
	 * Can be overridden by inheriting model classes.
	 * 
	 * @return array
	 */
	public function default_values() {
		return array();
	}
	
	public function delete() {
		global $wpdb;
		
		$sql = 'DELETE FROM '
		     . static::table_name()
		     . ' WHERE id = ' . $this->id;

		$rows_affected = $wpdb->query( $sql );

	    do_action('podlove_model_delete', $this);
	    do_action('podlove_model_change', $this);

		return $rows_affected !== false;
	}

	private function property_name_to_sql_update_statement( $p ) {
		global $wpdb;

		if ( $this->$p !== NULL && $this->$p !== '' ) {
			return sprintf( "%s = '%s'", $p, esc_sql($this->$p) );
		} else {
			return "$p = NULL";
		}
	}
	
	private function property_name_to_sql_value( $p ) {
		global $wpdb;

		if ( $this->$p !== NULL && $this->$p !== '' ) {
			return sprintf( "'%s'", $this->$p );
		} else {
			return 'NULL';
		}
	}
	
	/**
	 * Create database table based on defined properties.
	 * 
	 * Automatically includes an id column as auto incrementing primary key.
	 * @todo allow model changes
	 */
	public static function build() {
		global $wpdb;
		
		$property_sql = array();
		foreach ( self::properties() as $property )
			$property_sql[] = "`{$property['name']}` {$property['type']}";
		
		$sql = 'CREATE TABLE IF NOT EXISTS '
		     . static::table_name()
		     . ' ('
		     . implode( ',', $property_sql )
		     . ' ) CHARACTER SET utf8;'
		;
		
		$wpdb->query( $sql );

		self::build_indices();
	}
	
	/**
	 * Convention based index generation.
	 *
	 * Creates default indices for all columns matching both:
	 * - equals "id" or contains "_id"
	 * - doesn't have an index yet
	 */
	public static function build_indices() {
		global $wpdb;

		$indices_sql = 'SHOW INDEX FROM `' . static::table_name() . '`';
		$indices = $wpdb->get_results( $indices_sql );
		$index_columns = array_map( function($index){ return $index->Column_name; }, $indices );

		foreach ( self::properties() as $property ) {

			if ( $property['index'] && ! in_array( $property['name'], $index_columns ) ) {
				$length = isset($property['index_length']) ? '(' . (int) $property['index_length'] . ')' : '';
				$unique = isset($property['unique']) && $property['unique'] ? 'UNIQUE' : '';
				$sql = 'ALTER TABLE `' . static::table_name() . '` ADD ' . $unique . ' INDEX `' . $property['name'] . '` (' . $property['name'] . $length . ')';
				$wpdb->query( $sql );
			}
		}
	}

	/**
	 * Retrieves the database table name.
	 * 
	 * The name is derived from the namespace an class name. Additionally, it
	 * is prefixed with the global WordPress database table prefix.
	 * 
	 * @return string database table name
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::name();
	}

	public static function table_exists() {
		global $wpdb;
		$sql = $wpdb->prepare("SHOW TABLES LIKE %s", \Podlove\esc_like(self::table_name()));
		return $wpdb->get_var($sql) !== null;
	}

	/**
	 * Model identifier.
	 */
	public static function name() {
		// get name of implementing class
		$table_name = get_called_class();
		// replace backslashes from namespace by underscores
		$table_name = str_replace( '\\', '_', $table_name );
		// remove Models subnamespace from name
		$table_name = str_replace( 'Model_', '', $table_name );
		// all lowercase
		$table_name = strtolower( $table_name );

		return $table_name;
	}
	
	public static function destroy() {
		global $wpdb;
		$wpdb->query( 'DROP TABLE ' . static::table_name() );
	}

	public static function delete_all($reset_autoincrement = true) {
	    global $wpdb;
	    $wpdb->query( 'TRUNCATE ' . static::table_name() );  

	    if ($reset_autoincrement)
	    	$wpdb->query( 'ALTER TABLE ' . static::table_name() . ' AUTO_INCREMENT = 1' );  
	}

	public static function find_one_by_sql($sql) {
		global $wpdb;
		
		$class = get_called_class();
		$model = new $class();
		$model->flag_as_not_new();
		
		$row = $wpdb->get_row($sql);
		
		if ( ! $row ) {
			return NULL;
		}
		
		foreach ( $row as $property => $value ) {
			$model->$property = $value;
		}
		
		return $model;
	}

	public static function find_all_by_sql($sql) {
		global $wpdb;
		
		$class = get_called_class();
		$models = array();
		
		$rows = $wpdb->get_results($sql);
		
		if ( ! $rows )
			return array();
		
		foreach ( $rows as $row ) {
			$model = new $class();
			$model->flag_as_not_new();
			foreach ( $row as $property => $value ) {
				$model->$property = $value;
			}
			$models[] = $model;
		}
		
		return $models;
	}
}