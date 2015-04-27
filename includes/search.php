<?php
/**
 * Extend/Replace WordPress core search logic to include episode fields.
 *
 * The way I do it here is not well-behaving. If other plugins modify the query
 * before me, their changes will be overridden. However, there is no better
 * place to hook into and I refuse to modify the filterable query string with
 * regular expressions.
 *
 * If you found this piece of code and are now cursing at me, please get in
 * touch. 
 */
add_filter('posts_search', function($search, $query) {
	global $wpdb;

	if (!isset($query->query_vars['search_terms']))
		return $search;
		
	if ( isset( $query->query_vars['suppress_filters'] ) && true == $query->query_vars['suppress_filters'] )
		return $search;

	$episodesTable = \Podlove\Model\Episode::table_name();

	$search = '';
	$searchand = '';
	$n = !empty($query->query_vars['exact']) ? '' : '%';
	foreach( (array) $query->query_vars['search_terms'] as $term ) {
		$term = esc_sql( $wpdb->esc_like( $term ) );
		$search .= "
			{$searchand}
			(
				($wpdb->posts.post_title LIKE '{$n}{$term}{$n}')
				OR
				($wpdb->posts.post_content LIKE '{$n}{$term}{$n}')
				OR
				($episodesTable.subtitle LIKE '{$n}{$term}{$n}')
				OR
				($episodesTable.summary LIKE '{$n}{$term}{$n}')
				OR
				($episodesTable.chapters LIKE '{$n}{$term}{$n}')
			)";
		$searchand = ' AND ';
	}

	if ( !empty($search) ) {
		$search = " AND ({$search}) ";
		if ( !is_user_logged_in() )
			$search .= " AND ($wpdb->posts.post_password = '') ";
	}

	return $search;
}, 10, 2);

// join into episode table in WordPress searches so we can access episode fields
add_filter('posts_join', function($join, $query) {
	global $wpdb;

	if ($query->is_feed())
		return $join;

	if (!$query->is_search())
		return $join;

	$episodesTable = \Podlove\Model\Episode::table_name();
	$join .= " LEFT JOIN $episodesTable ON $wpdb->posts.ID = $episodesTable.post_id ";

	return $join;
}, 10, 2);