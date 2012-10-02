<?php
namespace Podlove;
use \Podlove\Model;

/**
 * Custom Post Type: "podcast"
 */
class Podcast_Post_Type {

	const SETTINGS_PAGE_HANDLE = 'podlove_settings_handle';

	public static $default_post_content = <<<EOT

[podlove-web-player]

[podlove-episode-downloads]
EOT;

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
			
		$slug = trim( \Podlove\get_setting( 'custom_episode_slug' ) );

		$args = array(
			'labels'               => $labels,
			'public'               => true,
			'publicly_queryable'   => true,
			'show_ui'              => true, 
			'show_in_menu'         => true, 
			'menu_position'        => 5, // below "Posts"
			'query_var'            => true,
			'rewrite'              => true,
			'capability_type'      => 'post',
			'has_archive'          => true, 
			'supports'             => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'trackbacks' ),
			'register_meta_box_cb' => '\Podlove\Podcast_Post_Meta_Box::add_meta_box',
			'menu_icon'            => PLUGIN_URL . '/images/episodes-icon-16x16.png',
			'rewrite' => array(
				'slug'       => strlen( $slug ) ? $slug : 'podcast',
				'with_front' => false
			)
		);

		if ( strlen( $slug ) === 0 )
			\Podlove\Episode_Routing::init();
		
		new \Podlove\Podcast_Post_Meta_Box();

		$args = apply_filters( 'podlove_post_type_args', $args );
		
		register_post_type( 'podcast', $args );
		
		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		add_filter( 'default_content', array( $this, 'set_default_episode_content' ), 20, 2 );	
		add_action( 'after_delete_post', array( $this, 'delete_trashed_episodes' ) );	
		
		if ( is_admin() ) {
			add_action( 'podlove_list_shows', array( $this, 'list_shows' ) );
			add_action( 'podlove_list_formats', array( $this, 'list_formats' ) );
			
			wp_register_script(
				'podlove_admin_episode',
				\Podlove\PLUGIN_URL . '/js/admin/episode.js',
				array( 'jquery' ),
				'1.1' 
			);

			wp_register_script(
				'podlove_admin_dashboard_validation',
				\Podlove\PLUGIN_URL . '/js/admin/dashboard_validation.js',
				array( 'jquery' ),
				'1.1' 
			);

			wp_register_script(
				'podlove_admin_media_location_settings',
				\Podlove\PLUGIN_URL . '/js/admin/media_location_settings.js',
				array( 'jquery' ),
				'1.1' 
			);

			wp_register_script(
				'podlove_admin',
				\Podlove\PLUGIN_URL . '/js/admin.js',
				array(
					'jquery',
					'podlove_admin_episode',
					'podlove_admin_dashboard_validation',
					'podlove_admin_media_location_settings'
				),
				'1.0' 
			);

			wp_enqueue_script( 'podlove_admin' );
		}
		
		add_filter( 'request', array( $this, 'add_post_type_to_feeds' ) );

		add_filter( 'get_the_excerpt', array( $this, 'default_excerpt_to_episode_summary' ) );
		
		\Podlove\Feeds\init();
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
			/* $function   */ function () { /* see \Podlove\Settings\Dashboard */ },
			/* $icon_url   */ PLUGIN_URL . '/images/podlove-icon-16x16.png'
			/* $position   */
		);

		new \Podlove\Settings\Dashboard( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Settings( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Podcast( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Format( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\MediaLocation( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\Feed( self::SETTINGS_PAGE_HANDLE );
		new \Podlove\Settings\WebPlayer( self::SETTINGS_PAGE_HANDLE );
		// new \Podlove\Settings\Show( self::SETTINGS_PAGE_HANDLE );
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

			if ( empty( $query_var['post_type'] ) ) {
				$query_var['post_type'] = $extend;
			} else {
				$query_var['post_type'] = array_merge( $query_var['post_type'], $extend );
			}
		}

		return $query_var;
	}

	public function set_default_episode_content( $post_content, $post ) {

		if ( $post->post_type !== 'podcast' )
			return $post_content;

		$post_content = $post_content . self::$default_post_content;

		return $post_content;
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

