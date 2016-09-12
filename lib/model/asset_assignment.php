<?php
namespace Podlove\Model;

/**
 * Simplified Singleton model for episode assignment data.
 */
class AssetAssignment {

	/**
	 * Contains property values.
	 * @var  array
	 */
	private $data = array();

	/**
	 * Contains property names.
	 * @var array
	 */
	protected static $properties = [];

	/**
	 * @return \Podlove\Model\AssetAssignment
	 */
	static public function get_instance() {
		 return new self;
	}

	protected function __construct() {
		$this->fetch();
	}

	final private function __clone() { }

	public function __set( $name, $value ) {
		if ( $this->has_property( $name ) ) {
			$this->set_property( $name, $value );
		} else {
			$this->$name = $value;
		}
	}

	private function set_property( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	public function __get( $name ) {
		if ( $this->has_property( $name ) ) {
			return $this->get_property( $name );
		} else {
			return $this->$name;
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
	 * Return a list of property dictionaries.
	 *
	 * @return array property list
	 */
	private function properties() {

		if ( ! isset( self::$properties ) )
			self::$properties = [];

		return self::$properties;
	}

	/**
	 * Does the given property exist?
	 *
	 * @param string $name name of the property to test
	 * @return bool True if the property exists, else false.
	 */
	public function has_property( $name ) {
		return in_array( $name, $this->property_names() );
	}

	/**
	 * Return a list of property names.
	 *
	 * @return array property names
	 */
	public function property_names() {
		return array_map( function ( $p ) { return $p['name']; } , self::$properties );
	}

	/**
	 * Define a property with by name.
	 *
	 * @param string $name Name of the property / column
	 */
	public static function property( $name ) {

		if ( ! isset( self::$properties ) )
			self::$properties = [];

		array_push( self::$properties, ['name' => $name] );
	}

	/**
	 * Save current state to database.
	 */
	public function save() {
		update_option( 'podlove_asset_assignment', $this->data );
	}

	/**
	 * Load podcast data.
	 */
	private function fetch() {
		$this->data = get_option( 'podlove_asset_assignment', array() );
	}

	/**
	 * Generate a human readable title.
	 *
	 * Return name and, if available, the subtitle. Separated by a dash.
	 *
	 * @return string
	 */
	public function full_title() {
		$t = $this->title;

		if ( $this->subtitle )
			$t = $t . ' - ' . $this->subtitle;

		return $t;
	}
}

AssetAssignment::property( 'image' );
