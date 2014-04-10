<?php 
namespace Podlove\Modules\Networks\Model;

use \Podlove\Model\Base;

/**
 * Simplified Singleton model for network data.
 *
 * There is only one Network, that's why this is a singleton.
 * Data handling is still similar to the other models. Storage is different.
 */
class Network extends Base {

	/**
	 * Override Base::table_name() to get the right prefix
	 */
	public static function table_name() {
		global $wpdb;
		
		// Switching to the first blog in network (contains network tables) (It is always 1!)
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
	*  Fetch Podcasts by Network
	*/
	public static function fetch_podcasts_by_network( $network_id ) {
		$network = self::find_by_id( $network_id );
		if( !isset( $network ) ) 
			return;

		$podcasts = array();
		foreach ( explode( ',', $network->podcasts ) as $podcast ) {
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
	 * Fetch all Podcasts
	 */
	public static function all_podcasts() {
		global $wpdb;
		return $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
	}

	/**
	 * Fetch all Podcasts ordered
	 */
	public static function all_podcasts_ordered( $sortby = "title", $sort = 'ASC' ) {
		$blog_ids = static::all_podcasts();

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
	 * Fetch statistics for the network
	 */
	public static function statistics() {
		$networks = count( self::all() );
		
	}

}

Network::property( 'title', 'VARCHAR(255)' );
Network::property( 'subtitle', 'TEXT' );
Network::property( 'description', 'TEXT' );
Network::property( 'url', 'TEXT' );
Network::property( 'logo', 'TEXT' );
Network::property( 'podcasts', 'TEXT' );