<?php
namespace Podlove\Model;

/**
 * Simplified model for podcast data.
 */
class Podcast implements Licensable {

	/**
	 * Contains property values.
	 * @var  array
	 */
	private $data = [];

	/**
	 * Contains property names.
	 * @var array
	 */
	protected static $properties = [];

	private $blog_id = NULL;

	public static function get($blog_id = NULL) {

		if (!$blog_id)
			$blog_id = get_current_blog_id();

		return new self($blog_id);
	}

	protected function __construct($blog_id) {
		$this->blog_id = $blog_id;
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

	public function get_blog_id() {
		return $this->blog_id;
	}

	public static function name() {
		return 'podcast';
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
		$this->set_property( 'media_file_base_uri', trailingslashit( $this->media_file_base_uri ) );

		self::with_blog_scope($this->blog_id, function() {

			update_option( 'podlove_podcast', $this->data );

			do_action('podlove_model_save', $this);
			do_action('podlove_model_change', $this);
		});
	}

	/**
	 * Load podcast data.
	 */
	private function fetch() {
		$this->data = self::with_blog_scope($this->blog_id, function() {
			return get_option('podlove_podcast', []);
		});
	}

	/**
	 * Execute block with proper blog scope.
	 * 
	 * If the given blog id is different from the current one, the scope is
	 * switches. Otherwise, the callback is just executed.
	 * 
	 * @param  int      $blog_id
	 * @param  callable $callback
	 * @return mixed
	 */
	public static function with_blog_scope($blog_id, $callback) {
		$result = NULL;

		if ($blog_id != get_current_blog_id()) {
			switch_to_blog($blog_id);
			$result = $callback();
			restore_current_blog();
		} else {
			$result = $callback();
		}

		return $result;
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
		
		if ($this->subtitle)
			$t = $t . ' - ' . $this->subtitle;
		
		return $t;
	}

	public function get_license()
	{
		$license = new License('podcast', [
			'license_name' => $this->license_name,
			'license_url'  => $this->license_url
		]);
		
		return $license;
	}

	public function get_license_picture_url() {
		return $this->get_license()->getPictureUrl();
	}

	public function get_license_html() {
		return $this->get_license()->getHtml();
	}

	public function get_url_template() {
		return self::with_blog_scope($this->blog_id, function() {
			return \Podlove\get_setting( 'website', 'url_template' );
		});
	}
}

Podcast::property( 'title' );
Podcast::property( 'subtitle' );
Podcast::property( 'cover_image' );
Podcast::property( 'summary' );
Podcast::property( 'author_name' );
Podcast::property( 'owner_name' );
Podcast::property( 'owner_email' );
Podcast::property( 'publisher_name' );
Podcast::property( 'publisher_url' );
Podcast::property( 'license_type' );
Podcast::property( 'license_name' );
Podcast::property( 'license_url' );
Podcast::property( 'license_cc_allow_modifications' );
Podcast::property( 'license_cc_allow_commercial_use' );
Podcast::property( 'license_cc_license_jurisdiction' );
Podcast::property( 'keywords' );
Podcast::property( 'category_1' );
Podcast::property( 'category_2' );
Podcast::property( 'category_3' );
Podcast::property( 'explicit' );
Podcast::property( 'label' );
Podcast::property( 'episode_prefix' );
Podcast::property( 'media_file_base_uri' );
Podcast::property( 'uri_delimiter' );
Podcast::property( 'limit_items' );
Podcast::property( 'language' );
Podcast::property( 'complete' );
Podcast::property( 'flattr' );
