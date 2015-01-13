<?php
namespace Podlove;
use \Podlove\Model;

/**
 * Custom Post Type: "podcast"
 */
class Podcast_Post_Type {

	const SETTINGS_PAGE_HANDLE = 'podlove_settings_handle';

	public function __construct() {

		$labels = array(
			'name'               => __( 'Episodes', 'podlove' ),
			'singular_name'      => __( 'Episode', 'podlove' ),
			'add_new'            => __( 'Add New', 'podlove' ),
			'add_new_item'       => __( 'Add New Episode', 'podlove' ),
			'edit_item'          => __( 'Edit Episode', 'podlove' ),
			'new_item'           => __( 'New Episode', 'podlove' ),
			'all_items'          => __( 'All Episodes', 'podlove' ),
			'view_item'          => __( 'View Episode', 'podlove' ),
			'search_items'       => __( 'Search Episodes', 'podlove' ),
			'not_found'          => __( 'No episodes found', 'podlove' ),
			'not_found_in_trash' => __( 'No episodes found in Trash', 'podlove' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Episodes', 'podlove' ),
		);

		$args = array(
			'labels'               => $labels,
			'public'               => true,
			'publicly_queryable'   => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'menu_position'        => 5, // below "Posts"
			'query_var'            => true,
			'rewrite'              => false, // we create our own permastructs
			'has_archive'          => false, // we create our own permastructs
			'capability_type'      => 'post',
			'supports'             => array( 'title', 'editor', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields', 'trackbacks' ),
			'register_meta_box_cb' => '\Podlove\Podcast_Post_Meta_Box::add_meta_box',
			'menu_icon' => 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxNi4wLjQsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCIgWw0KCTwhRU5USVRZIG5zX2V4dGVuZCAiaHR0cDovL25zLmFkb2JlLmNvbS9FeHRlbnNpYmlsaXR5LzEuMC8iPg0KCTwhRU5USVRZIG5zX2FpICJodHRwOi8vbnMuYWRvYmUuY29tL0Fkb2JlSWxsdXN0cmF0b3IvMTAuMC8iPg0KCTwhRU5USVRZIG5zX2dyYXBocyAiaHR0cDovL25zLmFkb2JlLmNvbS9HcmFwaHMvMS4wLyI+DQoJPCFFTlRJVFkgbnNfdmFycyAiaHR0cDovL25zLmFkb2JlLmNvbS9WYXJpYWJsZXMvMS4wLyI+DQoJPCFFTlRJVFkgbnNfaW1yZXAgImh0dHA6Ly9ucy5hZG9iZS5jb20vSW1hZ2VSZXBsYWNlbWVudC8xLjAvIj4NCgk8IUVOVElUWSBuc19zZncgImh0dHA6Ly9ucy5hZG9iZS5jb20vU2F2ZUZvcldlYi8xLjAvIj4NCgk8IUVOVElUWSBuc19jdXN0b20gImh0dHA6Ly9ucy5hZG9iZS5jb20vR2VuZXJpY0N1c3RvbU5hbWVzcGFjZS8xLjAvIj4NCgk8IUVOVElUWSBuc19hZG9iZV94cGF0aCAiaHR0cDovL25zLmFkb2JlLmNvbS9YUGF0aC8xLjAvIj4NCl0+DQo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkViZW5lXzEiIHhtbG5zOng9IiZuc19leHRlbmQ7IiB4bWxuczppPSImbnNfYWk7IiB4bWxuczpncmFwaD0iJm5zX2dyYXBoczsiDQoJIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iMTI4cHgiIGhlaWdodD0iMTI4cHgiDQoJIHZpZXdCb3g9IjAgMCAxMjggMTI4IiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAxMjggMTI4IiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxtZXRhZGF0YT4NCgk8c2Z3ICB4bWxucz0iJm5zX3NmdzsiPg0KCQk8c2xpY2VzPjwvc2xpY2VzPg0KCQk8c2xpY2VTb3VyY2VCb3VuZHMgIGhlaWdodD0iMTIzLjEiIHdpZHRoPSIxMjcuODg5IiBib3R0b21MZWZ0T3JpZ2luPSJ0cnVlIiB4PSItMC40NDQiIHk9IjMuNCI+PC9zbGljZVNvdXJjZUJvdW5kcz4NCgk8L3Nmdz4NCjwvbWV0YWRhdGE+DQo8Zz4NCgk8Zz4NCgkJPHBhdGggZmlsbD0iI0ZGRkZGRiIgZD0iTTM0LjczNyw1NS43MzdjMC0xLjU1My0wLjIyNC0zLjA1MS0wLjYwNy00LjQ4NGw5MC4wNDYtMjQuMTI5TDExNy40NDUsMkwxMC42NjUsMzAuNjEzbDIuMjQ4LDguMzkNCgkJCWMtNy40LDEuOTc5LTEyLjg1Nyw4LjcxMS0xMi44NTcsMTYuNzM0YzAsOS41NzgsNy43NjQsMTcuMzQxLDE3LjM0MSwxNy4zNDFWMTI1LjFoMTEwLjU0OFY1NS43MzdIMzQuNzM3eiBNMTcuMzk2LDYyLjY3NA0KCQkJYy0zLjgzMiwwLTYuOTM3LTMuMTA2LTYuOTM3LTYuOTM3YzAtMy44MzIsMy4xMDUtNi45MzcsNi45MzctNi45MzdzNi45MzcsMy4xMDUsNi45MzcsNi45MzcNCgkJCUMyNC4zMzMsNTkuNTY4LDIxLjIyOCw2Mi42NzQsMTcuMzk2LDYyLjY3NHogTTI4LjIzNCw3Ny40MTRsMTcuMzE4LTE3LjM0Mkg1Ni4zOUwzOS4wNzMsNzcuNDE0SDI4LjIzNHogTTQ5LjkxMSw3Ny40MTQNCgkJCWwxNy4zMTctMTcuMzQyaDEwLjgzOEw2MC43NDksNzcuNDE0SDQ5LjkxMXogTTcxLjU4Nyw3Ny40MTRsMTcuMzE3LTE3LjM0MmgxMC44MzhMODIuNDI1LDc3LjQxNEg3MS41ODd6IE0xMDQuMTAyLDc3LjQxNEg5My4yNjQNCgkJCWwxNy4zMTYtMTcuMzQyaDEwLjgzOEwxMDQuMTAyLDc3LjQxNHoiLz4NCgk8L2c+DQo8L2c+DQo8L3N2Zz4NCg==',
			'taxonomies' => array( 'post_tag' )
		);

		new \Podlove\Podcast_Post_Meta_Box();

		$args = apply_filters( 'podlove_post_type_args', $args );

		register_post_type( 'podcast', $args );

		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		
		add_action( 'admin_menu', array( $this, 'create_modules_menu_entry' ), 100 );
		add_action( 'admin_menu', array( $this, 'create_expert_settings_menu_entry' ), 200 );
		add_action( 'admin_menu', array( $this, 'create_support_menu_entry' ), 300 );

		add_action( 'after_delete_post', array( $this, 'delete_trashed_episodes' ) );
		add_filter( 'pre_get_posts', array( $this, 'enable_tag_and_category_search' ) );
		add_filter( 'post_class', array( $this, 'add_post_class' ) );
		add_filter( 'close_comments_for_post_types', array( $this, 'compatibility_with_auto_comment_closing' ) );

		$version = \Podlove\get_plugin_header( 'Version' );

		if ( is_admin() ) {
			add_action( 'podlove_list_shows', array( $this, 'list_shows' ) );
			add_action( 'podlove_list_formats', array( $this, 'list_formats' ) );

			wp_register_script(
				'podlove_admin_episode',
				\Podlove\PLUGIN_URL . '/js/admin/episode.js',
				array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin_dashboard_asset_validation',
				\Podlove\PLUGIN_URL . '/js/admin/dashboard_asset_validation.js',
				array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin_dashboard_feed_validation',
				\Podlove\PLUGIN_URL . '/js/admin/dashboard_feed_validation.js',
			 	array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin_episode_asset_settings',
				\Podlove\PLUGIN_URL . '/js/admin/episode_asset_settings.js',
				array( 'jquery', 'jquery-ui-sortable' ),
				$version
			);

			wp_register_script(
				'podlove_admin_episode_feed_settings',
				\Podlove\PLUGIN_URL . '/js/admin/feed_settings.js',
				array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin_autogrow',
				\Podlove\PLUGIN_URL . '/js/admin/jquery.autogrow.js',
				array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin_chosen',
				\Podlove\PLUGIN_URL . '/js/admin/chosen/chosen.jquery.min.js',
				array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin_chosen_image',
				\Podlove\PLUGIN_URL . '/js/admin/chosen/chosenImage.jquery.js',
				array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin_count_characters',
				\Podlove\PLUGIN_URL . '/js/admin/jquery.count_characters.js',
				array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin_license',
				\Podlove\PLUGIN_URL . '/js/admin/license.js',
				array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin_protected_feed',
				\Podlove\PLUGIN_URL . '/js/admin/protected_feed.js',
				array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin_data_table',
				\Podlove\PLUGIN_URL . '/js/admin/podlove_data_table.js',
				array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin',
				\Podlove\PLUGIN_URL . '/js/admin.js',
				array(
					'jquery',
					'jquery-ui-datepicker',
					'podlove_admin_episode',
					'podlove_admin_dashboard_asset_validation',
					'podlove_admin_dashboard_feed_validation',
					'podlove_admin_episode_asset_settings',
					'podlove_admin_episode_feed_settings',
					'podlove_admin_autogrow',
					'podlove_admin_count_characters',
					'podlove_admin_chosen',
					'podlove_admin_chosen_image',
					'podlove_admin_license',
					'podlove_admin_protected_feed',
					'podlove_admin_data_table'
				),
				$version
			);

			wp_enqueue_script( 'podlove_admin' );
			wp_enqueue_style( 'jquery-ui-style', \Podlove\PLUGIN_URL . '/js/admin/jquery-ui/css/smoothness/jquery-ui.css' );

		} else {
			wp_register_script(
				'podlove_frontend',
				\Podlove\PLUGIN_URL . '/js/frontend.js',
				array(
					'jquery'
				),
				$version
			);

			wp_enqueue_script( 'podlove_frontend' );
		}

		add_filter( 'get_the_excerpt', array( $this, 'default_excerpt_to_episode_summary' ) );
	}

	/**
	 * Add .post CSS class to post-classes to work around themes using the
	 * .post class to style articles.
	 *
	 * @param array $classes
	 * @return array
	 */
	function add_post_class( $classes ) {

		if ( get_post_type() == 'podcast' ) {
			$classes[] = 'post';
		}

		return $classes;
	}

	/**
	 * Add compatibility for automatic comment closing.
	 *
	 * WordPress has an option to automatically close commenting after some time.
	 * By default, it only works for "post" post types. But there is a hook to
	 * add post types.
	 *
	 * @param  array $post_types
	 * @return array
	 */
	public function compatibility_with_auto_comment_closing($post_types) {
		$post_types[] = 'podcast';
		return $post_types;
	}

	/**
	 * Enable tag and category search results for all post types.
	 *
	 * @param  mixed $query
	 * @return mixed
	 */
	public function enable_tag_and_category_search( $query ) {

		if ( ( is_category() || is_tag() ) && empty( $query->query_vars['suppress_filters'] ) ) {
			$post_type = get_query_var( 'post_type' );

			$query->set( 'post_type', $post_type ? $post_type : get_post_types() );

			return $query;
		}
	}

	public function default_excerpt_to_episode_summary( $excerpt ) {
		global $post;
    
		if ( get_post_type() == 'podcast' ) {
  		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post->ID );
      $excerpt = strlen( $episode->summary ) > 0 ? $episode->summary : $excerpt;
      $filtered = apply_filters("wp_trim_excerpt", $excerpt);
  		return $filtered;
		}
    else return $excerpt;
	}

	public function create_menu() {

		// create new top-level menu
		$hook = add_menu_page(
			/* $page_title */ 'Podlove Plugin Settings',
			/* $menu_title */ 'Podlove',
			/* $capability */ 'administrator',
			/* $menu_slug  */ self::SETTINGS_PAGE_HANDLE,
			/* $function   */ function () { /* see \Podlove\Settings\Dashboard */ },
			/* $icon_url   */ 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxNi4wLjQsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCIgWw0KCTwhRU5USVRZIG5zX2V4dGVuZCAiaHR0cDovL25zLmFkb2JlLmNvbS9FeHRlbnNpYmlsaXR5LzEuMC8iPg0KCTwhRU5USVRZIG5zX2FpICJodHRwOi8vbnMuYWRvYmUuY29tL0Fkb2JlSWxsdXN0cmF0b3IvMTAuMC8iPg0KCTwhRU5USVRZIG5zX2dyYXBocyAiaHR0cDovL25zLmFkb2JlLmNvbS9HcmFwaHMvMS4wLyI+DQoJPCFFTlRJVFkgbnNfdmFycyAiaHR0cDovL25zLmFkb2JlLmNvbS9WYXJpYWJsZXMvMS4wLyI+DQoJPCFFTlRJVFkgbnNfaW1yZXAgImh0dHA6Ly9ucy5hZG9iZS5jb20vSW1hZ2VSZXBsYWNlbWVudC8xLjAvIj4NCgk8IUVOVElUWSBuc19zZncgImh0dHA6Ly9ucy5hZG9iZS5jb20vU2F2ZUZvcldlYi8xLjAvIj4NCgk8IUVOVElUWSBuc19jdXN0b20gImh0dHA6Ly9ucy5hZG9iZS5jb20vR2VuZXJpY0N1c3RvbU5hbWVzcGFjZS8xLjAvIj4NCgk8IUVOVElUWSBuc19hZG9iZV94cGF0aCAiaHR0cDovL25zLmFkb2JlLmNvbS9YUGF0aC8xLjAvIj4NCl0+DQo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkViZW5lXzEiIHhtbG5zOng9IiZuc19leHRlbmQ7IiB4bWxuczppPSImbnNfYWk7IiB4bWxuczpncmFwaD0iJm5zX2dyYXBoczsiDQoJIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iMTI4cHgiIGhlaWdodD0iMTI4cHgiDQoJIHZpZXdCb3g9IjAgMCAxMjggMTI4IiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAxMjggMTI4IiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxtZXRhZGF0YT4NCgk8c2Z3ICB4bWxucz0iJm5zX3NmdzsiPg0KCQk8c2xpY2VzPjwvc2xpY2VzPg0KCQk8c2xpY2VTb3VyY2VCb3VuZHMgIGhlaWdodD0iMTI3Ljk4MyIgd2lkdGg9IjcyLjQyNCIgYm90dG9tTGVmdE9yaWdpbj0idHJ1ZSIgeD0iMjcuMzk2IiB5PSIwLjUwNSI+PC9zbGljZVNvdXJjZUJvdW5kcz4NCgk8L3Nmdz4NCjwvbWV0YWRhdGE+DQo8cGF0aCBmaWxsPSIjRkZGRkZGIiBkPSJNOTIuMjczLDEyNy45OTVIMzUuOTQzYy00LjQ0NCwwLTguMDQ3LTMuNTgxLTguMDQ3LTcuOTk5VjguMDExYzAtNC40MTcsMy42MDMtNy45OTksOC4wNDctNy45OTloNTYuMzMxDQoJYzQuNDQzLDAsOC4wNDcsMy41ODIsOC4wNDcsNy45OTl2MTExLjk4NUMxMDAuMzIsMTI0LjQxNCw5Ni43MTgsMTI3Ljk5NSw5Mi4yNzMsMTI3Ljk5NXogTTYzLjYwNSwxMTEuOTk2DQoJYzEzLjMzMywwLDI0LjE0MS0xMC43NDMsMjQuMTQxLTIzLjk5N2MwLTEzLjI1MS0xMC44MDktMjMuOTk1LTI0LjE0MS0yMy45OTVjLTEzLjMzMywwLTI0LjE0MSwxMC43NDQtMjQuMTQxLDIzLjk5NQ0KCUMzOS40NjQsMTAxLjI1Myw1MC4yNzMsMTExLjk5Niw2My42MDUsMTExLjk5NnogTTkyLjI3Myw4LjAxMUgzNS45NDN2NDcuOTkzaDU2LjMzMVY4LjAxMUw5Mi4yNzMsOC4wMTF6IE02My42MDUsNzkuMjQ2DQoJYzQuODY0LDAsOC44MDYsMy45Miw4LjgwNiw4Ljc1M2MwLDQuODM2LTMuOTQsOC43NTUtOC44MDYsOC43NTVjLTQuODY0LDAtOC44MDctMy45MTktOC44MDctOC43NTUNCglDNTQuNzk5LDgzLjE2Niw1OC43NDIsNzkuMjQ2LDYzLjYwNSw3OS4yNDZ6Ii8+DQo8cGF0aCBmaWxsPSIjRkZGRkZGIiBkPSJNNjMuOTkyLDIyLjk3MmM1LjAzMy0xMS4yNSwyMC4yOTktOS4wOTgsMjAuMzk4LDQuNTM0YzAuMDU3LDcuODA5LTIwLjM2OSwyMS44NzEtMjAuMzY5LDIxLjg3MQ0KCXMtMjAuNDctMTMuOTI5LTIwLjQxMy0yMS43ODlDNDMuNzA4LDEzLjk4OCw1OC43MTIsMTEuMjUzLDYzLjk5MiwyMi45NzJ6Ii8+DQo8L3N2Zz4NCg=='
			/* $position   */
		);

		new \Podlove\Settings\Dashboard( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Podcast( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\EpisodeAsset( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Feed( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Templates( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\FileType( self::SETTINGS_PAGE_HANDLE );

		do_action( 'podlove_register_settings_pages', self::SETTINGS_PAGE_HANDLE );
	}

	public function create_modules_menu_entry() {
		new \Podlove\Settings\Modules( self::SETTINGS_PAGE_HANDLE );
	}

	public function create_expert_settings_menu_entry() {
		new \Podlove\Settings\Settings( self::SETTINGS_PAGE_HANDLE );
	}

	public function create_support_menu_entry() {
		new \Podlove\Settings\Support( self::SETTINGS_PAGE_HANDLE );
	}

	/**
	 * Hook into post deletion and remove associated episode.
	 *
	 * @param int $post_id
	 */
	public function delete_trashed_episodes( $post_id ) {

		$episode = Model\Episode::find_one_by_post_id( $post_id );

		if ( ! $episode )
			return;

		if ( $media_files = Model\MediaFile::find_all_by_episode_id( $episode->id ) ) {
			foreach ( $media_files as $media_file ) {
				$media_file->delete();
			}
		}

		$episode->delete();
	}

}

