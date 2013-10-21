<?php

namespace Podlove\Model;

/**
 * Simplified Singleton model for podcast data.
 *
 * There is only one podcast, that's why this is a singleton.
 * Data handling is still similar to the other models. Storage is different.
 */
class Podcast {

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

	public function get_license_type() {
		return $this->license_type;
	}

	public function get_license() {
		$license_type = $this->license_type;
		switch ($license_type) {
			case 'cc':
				return array('license_type' => $license_type, 'license_attributes' => array(	'license_name' => "Creative Commons 3.0",
																								'license_url' => "http://creativecommons.org/licenses/by/3.0/",
																								'allow_modifications' => $this->license_cc_allow_modifications,
																								'allow_commercial_use' =>$this->license_cc_allow_commercial_use,
																								'jurisdiction' => $this->license_cc_license_jurisdiction));
			break;
			default :
				return array('license_type' => $license_type, 'license_attributes' => array(	'license_name' => $this->license_name,
																								'license_url' => $this->license_url));
			break;
		}
	}

	public function get_license_picture_url() {
		if($this->license_type == "cc") {
			switch ($this->license_cc_allow_modifications) {
				case "yes" :
					$banner_identifier_allowed_modification = 1;
				break;
				case "yesbutshare" :
					$banner_identifier_allowed_modification = 10;
				break;
				case "no" :
					$banner_identifier_allowed_modification = 0;
				break;
				default :
					$banner_identifier_allowed_modification = 1;
				break;
			}
			switch ($this->license_cc_allow_commercial_use) {
				case "yes" :
					$banner_identifier_commercial_use = 1;
				break;
				case "no" :
					$banner_identifier_commercial_use = 0;
				break;
				default :
					$banner_identifier_commercial_use = 1;
				break;
			}
			return \Podlove\PLUGIN_URL . "/images/cc/" . $banner_identifier_allowed_modification."_".$banner_identifier_commercial_use.".png";
		} 
	}

	public function license() {
		$locales = \Podlove\License\locales_cc();
		$versions = \Podlove\License\version_per_country_cc();
		switch ($this->license_type) {
			case 'cc' :
				if($this->license_cc_license_jurisdiction != "" AND
					$this->license_cc_allow_modifications != "" AND
					$this->license_cc_allow_commercial_use != "")  {
					if($this->license_cc_license_jurisdiction == "international") {
						$locale = "";
						$version = $versions["international"]["version"];
						$name = $versions["international"]["name"];
					} else {
						$locale = $this->license_cc_license_jurisdiction."/";
						$version = $versions[$this->license_cc_license_jurisdiction]["version"];
						$name = $locales[$this->license_cc_license_jurisdiction];
					}
					return "<div class=\"podlove_cc_license\">
					<img src=\"".$this->get_license_picture_url()."\" />
					<p>This work is licensed under a <a rel=\"license\" href=\"http://creativecommons.org/licenses/by/".$version."/".$locale."deed.en\">Creative Commons Attribution ".$version." ".$name." License</a>.</p>
					</div>";
				} else {
					return "<span style='color: red;'>This work is (not yet) licensed under a Creative Commons Attribution license, because some license parameters are missing!</span>";
				}
			break;
			case 'other' :
				if($this->license_name != "" AND $this->license_url != "") {
					return "<div class=\"podlove_license\">
								<p>This work is licensed under the <a rel=\"license\" href=\"".$this->license_url."\">".$this->license_name."</a> license.</p>
							</div>";
				} else {
					return "<span style='color: red;'>This work is (not yet) licensed, as license parameters are missing!</span>";
				}
			break;
			default :
				return "<div class=\"podlove_license\">
							<p><span style='color: red;'>This work is (not yet) licensed, as no license was chosen.</span></p>
						</div>";
			break;
		}
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
$podcast->property( 'episode_number_length' );
$podcast->property( 'language' );
$podcast->property( 'license_name' );
$podcast->property( 'license_url' );
// $podcast->property( 'url_template' );
