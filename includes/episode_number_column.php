<?php

add_filter('manage_edit-podcast_columns', 'podlove_add_episodeno_column_to_episodes_table' );
add_action('manage_podcast_posts_custom_column', 'podlove_add_episodeno_column_content_to_episodes_table' );

function podlove_add_episodeno_column_to_episodes_table($columns)
{
	$keys = array_keys($columns);
	$insertIndex = array_search('title', $keys); // before title column

	// insert downloads at that index
	$columns = array_slice($columns, 0, $insertIndex, true) +
	       array("episode_number" => __('Ep. #', 'podlove-podcasting-plugin-for-wordpress')) +
	       array_slice($columns, $insertIndex, count($columns) - 1, true);

	return $columns;
}

function podlove_add_episodeno_column_content_to_episodes_table($column_name)
{
	if ($column_name === 'episode_number') {
	    //check for null to prevent fatal error
        if(\Podlove\get_episode() != null) {
            echo \Podlove\get_episode()->number();
        }
	}
}
