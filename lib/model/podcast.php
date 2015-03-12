<?php
namespace Podlove\Model;

/**
 * Simplified model for podcast data.
 */
class Podcast implements Licensable {

	use KeepsBlogReferenceTrait;

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

	public static function get($blog_id = NULL) {
		return new self($blog_id);
	}

	protected function __construct($blog_id) {
		$this->set_blog_id($blog_id);
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

		$this->with_blog_scope(function() {

			update_option( 'podlove_podcast', $this->data );

			do_action('podlove_model_save', $this);
			do_action('podlove_model_change', $this);
		});
	}

	/**
	 * Load podcast data.
	 */
	private function fetch() {
		$this->data = $this->with_blog_scope(function() {
			return get_option('podlove_podcast', []);
		});
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
		return $this->with_blog_scope(function() {
			return \Podlove\get_setting( 'website', 'url_template' );
		});
	}

	public function episodes($args) {
		return $this->with_blog_scope(function() use ($args) {
			global $wpdb;

			// fetch single episodes
			if (isset($args['post_id']))
				return Episode::find_one_by_post_id($args['post_id']);

			if (isset($args['slug']))
				return Episode::find_one_by_slug($args['slug']);

			// build conditions
			$where = "1 = 1";
			$joins = "";
			if (isset($args['post_ids'])) {
				$ids = array_filter( // remove "0"-IDs
					array_map( // convert elements to integers
						function($n) { return (int) trim($n); },
						$args['post_ids']
					)
				);

				if (count($ids))
					$where .= " AND p.ID IN (" . implode(",", $ids) . ")";
			}

			if (isset($args['slugs'])) {
				$slugs = array_filter( // remove empty slugs
					array_map( // trim
						function($n) { return "'" . trim($n) . "'"; },
						$args['slugs']
					)
				);

				if (count($slugs))
					$where .= " AND e.slug IN (" . implode(",", $slugs) . ")";
			}

			if (isset($args['post_status']) && in_array($args['post_status'], get_post_stati())) {
				$where .= " AND p.post_status = '" . $args['post_status'] . "'";
			} else {
				$where .= " AND p.post_status = 'publish'";
			}

			if (isset($args['category']) && strlen($args['category'])) {
				$joins .= '
					JOIN ' . $wpdb->term_relationships . ' tr ON p.ID = tr.object_id
					JOIN ' . $wpdb->term_taxonomy . ' tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = "category"
					JOIN ' . $wpdb->terms . ' t ON t.term_id = tt.term_id AND t.slug = ' . $wpdb->prepare('%s', $args['category']) . '
				';
			}

			// order
			$order_map = array(
				'publicationDate' => 'p.post_date',
				'recordingDate'   => 'e.recordingDate',
				'slug'            => 'e.slug',
				'title'           => 'p.post_title'
			);

			if (isset($args['orderby']) && isset($order_map[$args['orderby']])) {
				$orderby = $order_map[$args['orderby']];
			} else {
				$orderby = $order_map['publicationDate'];
			}

			if (isset($args['order'])) {
				$args['order'] = strtoupper($args['order']);
				if (in_array($args['order'], array('ASC', 'DESC'))) {
					$order = $args['order'];
				} else {
					$order = 'DESC';
				}
			} else {
				$order = 'DESC';
			}

			if (isset($args['limit'])) {
				$limit = ' LIMIT ' . $args['limit'];
			} else {
				$limit = '';
			}

			$sql = '
				SELECT
					e.*
				FROM
					' . Episode::table_name() . ' e
					INNER JOIN ' . $wpdb->posts . ' p ON e.post_id = p.ID
					' . $joins . '
				WHERE ' . $where . '
				ORDER BY ' . $orderby . ' ' . $order . 
				$limit;

			$rows = $wpdb->get_results($sql);

			if (!$rows)
				return array();

			$episodes = array();
			foreach ($rows as $row) {
				$episode = new Episode();
				$episode->flag_as_not_new();
				foreach ( $row as $property => $value ) {
					$episode->$property = $value;
				}
				$episodes[] = $episode;
			}

			// filter out invalid episodes
			return array_filter($episodes, function($e) {
				return $e->is_valid();
			});	
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
