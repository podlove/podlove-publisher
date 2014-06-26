<?php 
namespace Podlove\Modules\Networks\Model;

use \Podlove\Model\Base;

/**
 * Lists are a model that can be used to organize Podcasts (e.g. networks)
 */
class PodcastList extends Base {

	/**
	 * Override Base::table_name() to get the right prefix
	 */
	public static function table_name() {
		global $wpdb;
		
		// Switching to the first blog in list (contains list tables) (It is always 1!)
		switch_to_blog(1);
		// get name of implementing class
		$table_name = get_called_class();
		// replace backslashes from namespace by underscores
		$table_name = str_replace( '\\', '_', $table_name );
		// remove Models subnamespace from name
		$table_name = str_replace( 'Model_', '', $table_name );
		// all lowercase
		$table_name = strtolower( $table_name );
		// prefix with $wpdb prefix
		return $wpdb->prefix . $table_name;
	}

	/** 
	*  Fetch Podcasts by List
	*/
	public static function fetch_podcasts_by_list( $list_id ) {
		$list = self::find_by_id( $list_id );
		if( !isset( $list ) ) 
			return;

		$podcasts = array();
		foreach ( explode( ',', $list->podcasts ) as $podcast ) {
			switch_to_blog( $podcast );
			$podcasts[ $podcast ] = \Podlove\Model\Podcast::get_instance();
		}
		return $podcasts;
	}

	/** 
	*  Fetch Podcast by ID
	*/
	public static function fetch_podcast_by_id( $id ) {
		switch_to_blog( $id );
		return \Podlove\Model\Podcast::get_instance();
	}

	/**
	 * Fetch all Blogs
	 */
	public static function all_blogs() {
		global $wpdb;
		return $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
	}


	/**
	 * Fetch all Podcasts
	 */
	public static function all_podcasts() {
		return array_filter( self::all_blogs(), function( $blog ) {
			switch_to_blog( $blog );
				if ( is_plugin_active( plugin_basename( \Podlove\PLUGIN_FILE ) ) )
					return $blog;
		} );
	}

	/**
	 * Fetch all Pocasts in the current list
	 */
	public function get_podcasts() {
		$podcasts = json_decode( $this->podcasts );
		$podcast_objects = array();
		foreach ($podcasts as $podcast) {
			switch ( $podcast->type ) {
				default: case 'wplist':
					switch_to_blog( $podcast->podcast );
					$podcast_intance = \Podlove\Model\Podcast::get_instance();
					$podcast_intance->blog_id = $podcast->podcast;
					$podcast_objects[] = $podcast_intance;
				break;
			}
		}

		return $podcast_objects;
	}

	/**
	 * Fetch all Podcasts ordered
	 */
	public static function all_podcasts_ordered( $sortby = "title", $sort = 'ASC' ) {
		$blog_ids = self::all_podcasts();

		foreach ($blog_ids as $blog_id) {
			switch_to_blog( $blog_id );
			$podcasts[ $blog_id ] = \Podlove\Model\Podcast::get_instance();
		}

		uasort( $podcasts, function ( $a, $b ) use ( $sortby, $sort ) {
			return strnatcmp( $a->$sortby, $b->$sortby );
		});

		if( $sort == 'DESC' ) {
			krsort( $podcasts );
		}

		return $podcasts;	
	}

	/**
	 * Fetch statistics for the list
	 */
	public static function statistics() {
		$lists = count( self::all() );
	}

}

PodcastList::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
PodcastList::property( 'title', 'VARCHAR(255)' );
PodcastList::property( 'slug', 'VARCHAR(255)' );
PodcastList::property( 'subtitle', 'TEXT' );
PodcastList::property( 'description', 'TEXT' );
PodcastList::property( 'url', 'TEXT' );
PodcastList::property( 'logo', 'TEXT' );
PodcastList::property( 'podcasts', 'TEXT' );