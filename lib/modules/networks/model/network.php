<?php
namespace Podlove\Modules\Networks\Model;

use \Podlove\Model\Podcast;

class Network {

	public static function blog_ids() {
		global $wpdb;

		if ($wpdb->blogs) {
			$blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs WHERE NOT archived");
		} else {
			$blogs = [];
		}

		return $blogs;
	}

	/**
	 * Fetch all blog IDs for Publisher blogs 
	 */
	public static function podcast_blog_ids() {
		return array_filter( Network::blog_ids(), function($blog_id) {
			return \Podlove\with_blog_scope($blog_id, function() {
				return is_plugin_active(plugin_basename(\Podlove\PLUGIN_FILE));
			});
		} );
	}

	/**
	 * Fetch all podcasts for Publisher blogs, ordered
	 */
	public static function podcasts( $sortby = "title", $sort = 'ASC' ) {

		$podcast_blog_ids = Network::podcast_blog_ids();

		if (empty($podcast_blog_ids))
			return [];

		foreach ($podcast_blog_ids as $blog_id) {
			$podcasts[$blog_id] = Podcast::get($blog_id);
		}

		uasort( $podcasts, function ( $a, $b ) use ( $sortby, $sort ) {
			return strnatcmp( $a->$sortby, $b->$sortby );
		});

		if ( $sort == 'DESC' )
			krsort( $podcasts );

		return $podcasts;	
	}
}