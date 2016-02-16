<?php
namespace Podlove;

class Downloads {
	
	/**
	 * Register hooks.
	 */
	public static function init() {
		/**
		 * Add "Downloads" column to episodes table
		 */
		add_filter('manage_edit-podcast_columns', array( __CLASS__, 'add_column_to_episodes_table') );
		add_action('manage_podcast_posts_custom_column', array( __CLASS__, 'add_column_content_to_episodes_table') );

		/**
		 * This is probably how you add sortability.
		 * However, it requires a "downloads" meta entry.
		 * To make this work, a cron has to periodically (hourly?) update the downloads
		 * meta value.
		 * 
		 *	add_filter('manage_edit-podcast_sortable_columns', function($columns) {
		 *		$columns['downloads'] = 'downloads';
		 *		return $columns;
		 *	});
		 *
		 *	add_action('pre_get_posts', function ($query) {
		 *
		 *	    if (!is_admin())
		 *	        return;
		 *	 
		 *	    $orderby = $query->get('orderby');
		 *	 
		 *	    if ('downloads' == $orderby) {
		 *	        $query->set('meta_key', 'downloads');
		 *	        $query->set('orderby', 'meta_value_num');
		 *	    }
		 *	});
		 *	
		 */
	}

	public static function add_column_to_episodes_table($columns) {
			$keys = array_keys($columns);
		    $insertIndex = array_search('date', $keys) + 1; // after date column

		    // insert downloads at that index
		    $columns = array_slice($columns, 0, $insertIndex, true) +
		           array("downloads" => __('Downloads', 'podlove-podcasting-plugin-for-wordpress')) +
			       array_slice($columns, $insertIndex, count($columns) - 1, true);

		    return $columns;
	}

	public static function add_column_content_to_episodes_table($column_name) {
		global $wpdb;

		switch ($column_name) {
			case 'downloads':
				$total = get_post_meta(get_the_ID(), '_podlove_downloads_total', true);
				if ($total && is_numeric($total)) {
					echo number_format_i18n($total);
				}
			break;
		}
	}

}
