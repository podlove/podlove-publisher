<?php 
namespace Podlove\Modules\Networks\Model;

use \Podlove\Model\Base;
use \Podlove\Model\Podcast;

/**
 * Lists are a model that can be used to organize Podcasts (e.g. networks)
 */
class PodcastList extends Base {

	use \Podlove\Model\NetworkTrait;

	/**
	 * Fetch all Pocasts in the current list
	 */
	public function podcasts() {
		$podcasts = json_decode( $this->podcasts );

		$podcast_objects = array();
		foreach ($podcasts as $podcast) {
			switch ( $podcast->type ) {
				default: case 'wplist':
					$podcast_objects[] = Podcast::get($podcast->podcast);
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

 		$podcasts = $this->podcasts();

 		// sanitize order
 		$order = $order == 'DESC' ? 'DESC' : 'ASC';

 		// sanitize orderby
 		$valid_orderby = [ 'post_date', 'post_title', 'ID', 'comment_count'	];
 		$orderby = in_array($orderby, $valid_orderby) ? $orderby : 'post_date';
 
 		// Generate mySQL Query
 		$subqueries = [];
 		foreach ( $podcasts as $podcast_key => $podcast ) {

 			$subqueries[] = $podcast->with_blog_scope(function() use ($podcast) {
 				global $wpdb;
	 
	 	        $query = "(SELECT p.ID, p.post_title, p.post_date, b.blog_id FROM " . $wpdb->posts . " p, " . $wpdb->blogs . " b\n";
	 	        $query .= "WHERE p.post_type = 'podcast'";
	 	        $query .= "AND p.post_status = 'publish'";
	 	        $query .= "AND b.blog_id = " . $podcast->get_blog_id() . ")";

	 	       return $query;
 			});

 		}
 
 		$query = implode("UNION\n", $subqueries) . " ORDER BY $orderby $order LIMIT 0, " . (int) $number_of_episodes;

       	$recent_posts = $wpdb->get_results( $query );
 
 		$episodes = [];
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