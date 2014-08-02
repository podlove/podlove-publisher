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
		$prefix = $wpdb->prefix . $table_name;

		restore_current_blog();
		return $prefix;
	}

	/** 
	*  Fetch Podcasts by List
	*/
	public static function fetch_podcasts_by_list( $list_id ) {
		$current_blog_id = get_current_blog_id();
		$list = self::find_by_id( $list_id );
		if( !isset( $list ) ) 
			return;

		$podcasts = array();
		foreach ( explode( ',', $list->podcasts ) as $podcast ) {
			switch_to_blog( $podcast );
			$podcasts[ $podcast ] = \Podlove\Model\Podcast::get_instance();
		}
		switch_to_blog( $current_blog_id );
		return $podcasts;
	}

	/** 
	*  Fetch Podcast by ID
	*/
	public static function fetch_podcast_by_id( $id ) {
		switch_to_blog( $id );
		$podcast = \Podlove\Model\Podcast::get_instance();
		restore_current_blog();
		return $podcast;
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
		$current_blog_id = get_current_blog_id();
		$podcasts = array_filter( self::all_blogs(), function( $blog ) {
			switch_to_blog( $blog );
				if ( is_plugin_active( plugin_basename( \Podlove\PLUGIN_FILE ) ) )
					return $blog;
		} );
		switch_to_blog( $current_blog_id );
		return $podcasts;
	}

	/**
	 * Fetch all Podcasts ordered
	 */
	public static function all_podcasts_ordered( $sortby = "title", $sort = 'ASC' ) {
		$current_blog_id = get_current_blog_id();
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

		switch_to_blog( $current_blog_id );
		return $podcasts;	
	}

	/**
	 * Fetch all Pocasts in the current list
	 */
	public function get_podcasts() {
		$current_blog_id = get_current_blog_id();
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

		switch_to_blog( $current_blog_id );
		return $podcast_objects;
	}

	/**
	 * Fetch episodes for the list
	 */
	public function latest_episodes( $number_of_episodes = "10", $orderby = "post_date", $order = "DESC" ) {
 		global $wpdb;
 		$current_blog_id = get_current_blog_id();

 		$podcasts = $this->podcasts;
 		$prefix = $wpdb->get_blog_prefix(0);
 		$prefix = str_replace( '1_', '' , $prefix );
 		$query = "";
 		$episodes = array();
 
 		// Generate mySQL Query
 		foreach ( $podcasts as $podcast_key => $podcast ) {
 			if( $podcast_key == 0 ) {
 			    $post_table = $prefix . "posts";
 			} else {
 			    $post_table = $prefix . $podcast->blog_id . "_posts";
 			}
 
 			$post_table = esc_sql( $post_table );
 	        $blog_table = esc_sql( $prefix . 'blogs' );
 
 	        $query .= "(SELECT $post_table.ID, $post_table.post_title, $post_table.post_date, $blog_table.blog_id FROM $post_table, $blog_table\n";
 	        $query .= "WHERE $post_table.post_type = 'podcast'";
 	        $query .= "AND $post_table.post_status = 'publish'";
 	        $query .= "AND $blog_table.blog_id = {$podcast->blog_id})";
 
 	        if( $podcast_key !== count( $podcasts ) - 1 ) 
 	           $query .= "UNION\n";
 	        else
 	           $query .= "ORDER BY $orderby $order LIMIT 0, $number_of_episodes";		
 		}
 
       	$recent_posts = $wpdb->get_results( $query );
 
       	foreach ( $recent_posts as $post ) {
    			switch_to_blog( $post->blog_id );
    			if ( $episode = \Podlove\Model\Episode::find_one_by_post_id( $post->ID ) )
    				$episodes[] = new \Podlove\Template\Episode( $episode );
       	}
 
 		switch_to_blog( $current_blog_id );
       	return $episodes;
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