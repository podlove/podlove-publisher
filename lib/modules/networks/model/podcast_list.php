<?php 
namespace Podlove\Modules\Networks\Model;

use \Podlove\Model\Base;

/**
 * Lists are a model that can be used to organize Podcasts (e.g. networks)
 */
class PodcastList extends Base {

	use \Podlove\Model\NetworkTrait;

	/** 
	*  Fetch Podcasts by List
	*/
	public static function fetch_podcasts_by_list( $list_id ) {
		
		if (!$list = self::find_by_id($list_id)) 
			return;

		$podcasts = [];
		foreach ( explode(',', $list->podcasts) as $podcast ) {
			switch_to_blog($podcast);
			$podcasts[$podcast] = \Podlove\Model\Podcast::get();
			restore_current_blog();
		}

		return $podcasts;
	}

	/** 
	*  Fetch Podcast by ID
	*/
	public static function fetch_podcast_by_id( $id ) {
		switch_to_blog( $id );
		$podcast = \Podlove\Model\Podcast::get();
		restore_current_blog();
		return $podcast;
	}

	/**
	 * Fetch all blog IDs
	 */
	public static function get_all_blog_ids() {
		global $wpdb;

		if ($wpdb->blogs) {
			$blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
		} else {
			$blogs = [];
		}

		return $blogs;
	}

	/**
	 * Fetch all blog IDs for Publisher blogs 
	 */
	public static function get_all_podcast_ids() {
		return array_filter( self::get_all_blog_ids(), function( $blog ) {
			switch_to_blog( $blog );
			if ( is_plugin_active( plugin_basename( \Podlove\PLUGIN_FILE ) ) ) {
				restore_current_blog();
				return $blog;
			} else {
				restore_current_blog();
				return false;
			}
		} );
	}

	/**
	 * Fetch all blog IDs for Publisher blogs, ordered
	 */
	public static function get_all_podcast_ids_ordered( $sortby = "title", $sort = 'ASC' ) {
		$blog_ids = self::get_all_podcast_ids();

		foreach ($blog_ids as $blog_id) {
			switch_to_blog( $blog_id );
			$podcasts[ $blog_id ] = \Podlove\Model\Podcast::get();
			restore_current_blog();
		}

		uasort( $podcasts, function ( $a, $b ) use ( $sortby, $sort ) {
			return strnatcmp( $a->$sortby, $b->$sortby );
		});

		if ( $sort == 'DESC' )
			krsort( $podcasts );

		return $podcasts;	
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
					$podcast_objects[] = \Podlove\Model\Podcast::get($podcast->podcast);
				break;
			}
		}

		return $podcast_objects;
	}

	/**
	 * Fetch episodes for the list
	 */
	public function latest_episodes( $number_of_episodes = 10, $orderby = "post_date", $order = "DESC" ) {
 		global $wpdb;

 		$podcasts = $this->podcasts;
 		$query = "";
 		$episodes = array();

 		// sanitize order
 		$order = $order == 'DESC' ? 'DESC' : 'ASC';

 		// sanitize orderby
 		$valid_orderby = [ 'post_date', 'post_title', 'ID', 'comment_count'	];
 		$orderby = in_array($orderby, $valid_orderby) ? $orderby : 'post_date';
 
 		// Generate mySQL Query
 		foreach ( $podcasts as $podcast_key => $podcast ) {
 			if ( $podcast_key == 0 ) {
 			    $post_table = $wpdb->base_prefix . "posts";
 			} else {
 			    $post_table = $wpdb->base_prefix . $podcast->blog_id . "_posts";
 			}
 
 			$post_table = esc_sql( $post_table );
 	        $blog_table = esc_sql( $wpdb->base_prefix . 'blogs' );
 
 	        $query .= "(SELECT $post_table.ID, $post_table.post_title, $post_table.post_date, $blog_table.blog_id FROM $post_table, $blog_table\n";
 	        $query .= "WHERE $post_table.post_type = 'podcast'";
 	        $query .= "AND $post_table.post_status = 'publish'";
 	        $query .= "AND $blog_table.blog_id = {$podcast->blog_id})";
 
 	        if ( $podcast_key !== count( $podcasts ) - 1 ) 
 	           $query .= "UNION\n";
 	        else
 	           $query .= "ORDER BY $orderby $order LIMIT 0, " . (int) $number_of_episodes;
 		}
 
       	$recent_posts = $wpdb->get_results( $query );
 
       	foreach ( $recent_posts as $post ) {
    			switch_to_blog( $post->blog_id );
    			if ( $episode = \Podlove\Model\Episode::find_one_by_post_id( $post->ID ) ) {
    				$episodes[] = new \Podlove\Template\Episode( $episode );
    			}
    			restore_current_blog();
       	}
 
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