<?php
namespace Podlove\Model;

/**
 * Simplified Singleton model for podcast data.
 *
 * There is only one podcast, that's why this is a singleton.
 * Data handling is still similar to the other models. Storage is different.
 */
class Podcast implements Licensable {

	/**
	 * Singleton instance container.
	 * @var \Podlove\Model\Podcast|NULL
	 */
	private static $instance = NULL;

	/**
	 * Contains property values.
	 * @var  array
	 */
	private $data = array();

	/**
	 * Contains property names.
	 * @var array
	 */
	protected $properties = array();

	private $blog_id = NULL;

	/**
	 * Singleton.
	 * 
	 * @return \Podlove\Model\Podcast
	 */
	static public function get_instance() {

		// whenever the blog is switched, we need to reload all podcast data
		if ( ! isset( self::$instance ) || self::$instance->blog_id != get_current_blog_id() ) {

			$properties = isset( self::$instance ) ? self::$instance->properties : false;
			self::$instance = new self;
			self::$instance->blog_id = get_current_blog_id();

			// only take properties from preexisting instances
			if ( $properties )
				self::$instance->properties = $properties;
		}

		return self::$instance;
	}

	protected function __construct() {
		$this->data = array();
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
		
		if ( ! isset( $this->properties ) )
			$this->properties = array();
		
		return $this->properties;
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
		return array_map( function ( $p ) { return $p['name']; } , $this->properties );
	}

	/**
	 * Define a property with by name.
	 * 
	 * @param string $name Name of the property / column
	 */
	public function property( $name ) {

		if ( ! isset( $this->properties ) )
			$this->properties = array();

		array_push( $this->properties, array( 'name' => $name ) );
	}

	/**
	 * Save current state to database.
	 */
	public function save() {
		$this->set_property( 'media_file_base_uri', trailingslashit( $this->media_file_base_uri ) );
		update_option( 'podlove_podcast', $this->data );
	}

	/**
	 * Load podcast data.
	 */
	private function fetch() {
		$this->data = get_option( 'podlove_podcast', array() );
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

	public function get_license()
	{
		$license = new License('podcast', array(
			'type'                 => $this->license_type,
			'license_name'         => $this->license_name,
			'license_url'          => $this->license_url,
			'allow_modifications'  => $this->license_cc_allow_modifications,
			'allow_commercial_use' => $this->license_cc_allow_commercial_use,
			'jurisdiction'         => $this->license_cc_license_jurisdiction
		));

		return $license;
	}

	public function get_license_picture_url() {
		return $this->get_license()->getPictureUrl();
	}

	public function get_license_html() {
		return $this->get_license()->getHtml();
	}

	public function get_url_template() {
		return \Podlove\get_setting( 'website', 'url_template' );
	}
}

$podcast = Podcast::get_instance();
$podcast->property( 'title' );
$podcast->property( 'subtitle' );
$podcast->property( 'cover_image' );
$podcast->property( 'summary' );
$podcast->property( 'author_name' );
$podcast->property( 'owner_name' );
$podcast->property( 'owner_email' );
$podcast->property( 'publisher_name' );
$podcast->property( 'publisher_url' );
$podcast->property( 'license_type' );
$podcast->property( 'license_name' );
$podcast->property( 'license_url' );
$podcast->property( 'license_cc_allow_modifications' );
$podcast->property( 'license_cc_allow_commercial_use' );
$podcast->property( 'license_cc_license_jurisdiction' );
$podcast->property( 'keywords' );
$podcast->property( 'category_1' );
$podcast->property( 'category_2' );
$podcast->property( 'category_3' );
$podcast->property( 'explicit' );
$podcast->property( 'label' );
$podcast->property( 'episode_prefix' );
$podcast->property( 'media_file_base_uri' );
$podcast->property( 'uri_delimiter' );
$podcast->property( 'limit_items' );
$podcast->property( 'language' );
$podcast->property( 'complete' );
$podcast->property( 'flattr' );
