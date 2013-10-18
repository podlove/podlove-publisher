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
			// 'menu_icon'            => PLUGIN_URL . '/images/episodes-icon-16x16.png',
			'taxonomies' => array( 'post_tag' )
		);

		new \Podlove\Podcast_Post_Meta_Box();

		$args = apply_filters( 'podlove_post_type_args', $args );

		register_post_type( 'podcast', $args );

		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		add_action( 'admin_menu', array( $this, 'create_support_menu_entry' ), 100 ); // make sure it's at the bottom
		add_action( 'after_delete_post', array( $this, 'delete_trashed_episodes' ) );
		add_filter( 'pre_get_posts', array( $this, 'enable_tag_and_category_search' ) );
		add_filter( 'post_class', array( $this, 'add_post_class' ) );

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
				'podlove_admin_dashboard_validation',
				\Podlove\PLUGIN_URL . '/js/admin/dashboard_validation.js',
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
				'podlove_admin_count_characters',
				\Podlove\PLUGIN_URL . '/js/admin/jquery.count_characters.js',
				array( 'jquery' ),
				$version
			);

			wp_register_script(
				'podlove_admin',
				\Podlove\PLUGIN_URL . '/js/admin.js',
				array(
					'jquery',
					'podlove_admin_episode',
					'podlove_admin_dashboard_validation',
					'podlove_admin_episode_asset_settings',
					'podlove_admin_episode_feed_settings',
					'podlove_admin_autogrow',
					'podlove_admin_count_characters',
					'podlove_admin_chosen'
				),
				$version
			);

			wp_enqueue_script( 'podlove_admin' );
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

		add_filter( 'request', array( $this, 'add_post_type_to_feeds' ) );

		add_filter( 'get_the_excerpt', array( $this, 'default_excerpt_to_episode_summary' ) );
	}

	/**
	 * Add .post CSS class to post-classes to work around themes using the
	 * .post class to style articles.
	 * 
	 * @param array $classes
	 */
	function add_post_class( $classes ) {

		if ( get_post_type() == 'podcast' ) {
			$classes[] = 'post';
		}

		return $classes;
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

		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post->ID );
		return $episode && strlen( $episode->summary ) > 0 ? $episode->summary : $excerpt;
	}

	public function create_menu() {

		// create new top-level menu
		$hook = add_menu_page(
			/* $page_title */ 'Podlove Plugin Settings',
			/* $menu_title */ 'Podlove',
			/* $capability */ 'administrator',
			/* $menu_slug  */ self::SETTINGS_PAGE_HANDLE,
			/* $function   */ function () { /* see \Podlove\Settings\Dashboard */ }
			/* $icon_url   */ //PLUGIN_URL . '/images/podlove-icon-16x16.png'
			/* $position   */
		);

		new \Podlove\Settings\Dashboard( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Podcast( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\EpisodeAsset( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Feed( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Contributors( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\WebPlayer( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Templates( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\FileType( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Modules( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Settings( self::SETTINGS_PAGE_HANDLE );
		
	}

	public function create_support_menu_entry() {
		new \Podlove\Settings\Support( self::SETTINGS_PAGE_HANDLE );
	}

	/**
	 * Add Custom Post Type to all WordPress Feeds.
	 *
	 * @todo  is this a good idea at all?
	 *
	 * @param array $query_var
	 * @return array
	 */
	function add_post_type_to_feeds( $query_var ) {

		if ( isset( $query_var['feed'] ) ) {

			$extend = array(
				'post' => 'post',
				'podcast' => 'podcast'
			);

			if ( empty( $query_var['post_type'] ) || ! is_array( $query_var['post_type'] ) ) {
				$query_var['post_type'] = $extend;
			} else {
				$query_var['post_type'] = array_merge( $query_var['post_type'], $extend );
			}
		}

		return $query_var;
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

