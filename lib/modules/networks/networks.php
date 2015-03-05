<?php 
namespace Podlove\Modules\Networks;

use \Podlove\Model;
use \Podlove\Modules\Networks\Model\PodcastList;
use \Podlove\Model\Template;

class Networks extends \Podlove\Modules\Base {

	protected $module_name = 'Podcast Network';
	protected $module_description = 'Support for Podcast Networks using <a href="http://codex.wordpress.org/Create_A_Network">WordPress Multisite</a> environments.';
	protected $module_group = 'system';

	public function load() {

		// skip module outsite multisite environments
		if (!is_multisite())
			return;

		// Actions after activation
		add_action( 'podlove_module_was_activated_networks', array( $this, 'was_activated' ) );

		// Adding Network Admin Menu
		add_action( 'network_admin_menu', array( $this, 'create_network_menu' ) );

		add_action( 'admin_bar_menu', array( $this, 'create_network_toolbar' ), 999 );

		// Twig template filter
		add_filter( 'podlove_templates_global_context', array( $this, 'twig_template_filter' ) );

		add_filter( 'podlove_system_report_fields', array( $this, 'add_system_report_validations' ) );

		// Styles
		add_action( 'admin_print_styles', array( $this, 'scripts_and_styles' ) );
		add_action( 'wp_print_styles', array( $this, 'scripts_and_styles' ) );
	}

	public function add_system_report_validations($fields)
	{
		$switch_to_main_blog = function() {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		};

		$is_active_in_main_blog = function() use ($switch_to_main_blog) {
			
			$switch_to_main_blog();
			$module_active = \Podlove\Modules\Base::is_active('networks');
			$plugin_active = is_plugin_active( plugin_basename( \Podlove\PLUGIN_FILE ) );
			restore_current_blog();

			return $module_active && $plugin_active;
		};

		$fields['network module'] = array(
			'callback' => function() use ($is_active_in_main_blog) {
				if ($is_active_in_main_blog()) {
					return "ok";
				} else {
					return array(
						"message" => __( "Must be active in main blog!", 'podlove' ),
						"error" => __( "You are using the network module. You have to activate it in your main WordPress blog to work properly", 'podlove' )
					);
				}
			}
		);

		return $fields;
	}

	public function twig_template_filter( $context ) {
		$podlove = new \Podlove\Modules\Networks\Template\Podlove;
		
		return array_merge($context, array( 'podlove' => $podlove ));
	}

	/*
	 *	Was activated
	 */
	public function was_activated( $module_name ) {
		PodcastList::activate_network_scope();
		PodcastList::build();
		PodcastList::deactivate_network_scope();
		
		Template::activate_network_scope();
		Template::build();
		Template::deactivate_network_scope();
	}

	/*
	 *  Register Network Toolbar Menu
	 */
	public function create_network_toolbar( $wp_admin_bar ) {
		$network_dashboard_url = network_site_url() . 'wp-admin/network/admin.php?page=podlove_network_settings_handle';
		PodcastList::activate_network_scope();
		$podcasts = PodcastList::get_all_podcast_ids();
		PodcastList::deactivate_network_scope();
		$podcast_admin_url = get_admin_url();

		// Podlove Toolbar Icon
		$args = array(
			'id'     => 'podlove_toolbar',
			'title'  => 'Podlove',
			'href'   => $network_dashboard_url,
			'meta'   => array( 'class' => 'podlove-toolbar-opener' )
		);
		$wp_admin_bar->add_node( $args );

		// Podlove Dashboard
		$args = array(
			'id'     => 'podlove_toolbar_dashboard',
			'title'  => __( 'Dashboard', 'podlove' ),
			'parent' => 'podlove_toolbar',
			'href'   => $podcast_admin_url . 'admin.php?page=podlove_settings_handle',
			'meta'   => array( 'class' => 'podlove-toolbar-without-icon' )
		);
		$wp_admin_bar->add_node( $args );

		// Podlove Episodes
		$args = array(
			'id'     => 'podlove_toolbar_episodes',
			'title'  => __( 'Episodes', 'podlove' ),
			'parent' => 'podlove_toolbar',
			'href'   => $podcast_admin_url . 'edit.php?post_type=podcast',
			'meta'   => array( 'class' => 'podlove-toolbar-without-icon' )
		);
		$wp_admin_bar->add_node( $args );

		do_action('podlove_network_admin_bar', $wp_admin_bar);

		// Register Podcasts
		foreach ( $podcasts as $podcast ) {
			switch_to_blog( $podcast );
			$podcast_data = \Podlove\Model\Podcast::get_instance();
			$podcast_toolbar_id = 'podlove_toolbar_' . $podcast;
			$podcast_admin_url = get_admin_url();

			$args = array(
				'id'     => $podcast_toolbar_id,
				'title'  => get_bloginfo( 'name' ),
				'parent' => 'podlove_toolbar',
				'href'   => $podcast_admin_url . 'admin.php?page=podlove_settings_handle',
				'meta'   => array( 
						'class' => 'podlove-toolbar-podcast podlove-toolbar-podcast-' . $podcast,
						'html'  => '<img class="podlove-toolbar-podcast-cover" src="' . $podcast_data->cover_image . '" alt="' . get_bloginfo( 'name' ) . '" />'
					)
			);
			$wp_admin_bar->add_node( $args );

			// Register Dashboard, Episodes and Contributor per Podcast
			$args = array(
				'id'     => $podcast_toolbar_id . '_dashboard',
				'title'  => __( 'Dashboard', 'podlove' ),
				'parent' => $podcast_toolbar_id,
				'href'   => $podcast_admin_url . 'admin.php?page=podlove_settings_handle',
				'meta'   => array( 
						'class' => 'podlove-toolbar-without-icon'
					)
			);
			$wp_admin_bar->add_node( $args );
			$args = array(
				'id'     => $podcast_toolbar_id . '_episodes',
				'title'  => __( 'Episodes', 'podlove' ),
				'parent' => $podcast_toolbar_id,
				'href'   => $podcast_admin_url . 'edit.php?post_type=podcast',
				'meta'   => array( 
						'class' => 'podlove-toolbar-without-icon'
					)
				
			);
			$wp_admin_bar->add_node( $args );

			do_action('podlove_network_admin_bar_podcast', $wp_admin_bar, $podcast);

			restore_current_blog();
		}


	}

	/*
	 *  Register Network Admin Menu
	 */
	public function create_network_menu() {

		// create new top-level menu
		$hook = add_menu_page(
			/* $page_title */ 'Podlove Plugin Settings',
			/* $menu_title */ 'Podlove',
			/* $capability */ 'administrator',
			/* $menu_slug  */ \Podlove\Podcast_Post_Type::NETWORK_SETTINGS_PAGE_HANDLE,
			/* $function   */ function () { /* see \Podlove\Settings\Dashboard */ },
			/* $icon_url   */ 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxNi4wLjQsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCIgWw0KCTwhRU5USVRZIG5zX2V4dGVuZCAiaHR0cDovL25zLmFkb2JlLmNvbS9FeHRlbnNpYmlsaXR5LzEuMC8iPg0KCTwhRU5USVRZIG5zX2FpICJodHRwOi8vbnMuYWRvYmUuY29tL0Fkb2JlSWxsdXN0cmF0b3IvMTAuMC8iPg0KCTwhRU5USVRZIG5zX2dyYXBocyAiaHR0cDovL25zLmFkb2JlLmNvbS9HcmFwaHMvMS4wLyI+DQoJPCFFTlRJVFkgbnNfdmFycyAiaHR0cDovL25zLmFkb2JlLmNvbS9WYXJpYWJsZXMvMS4wLyI+DQoJPCFFTlRJVFkgbnNfaW1yZXAgImh0dHA6Ly9ucy5hZG9iZS5jb20vSW1hZ2VSZXBsYWNlbWVudC8xLjAvIj4NCgk8IUVOVElUWSBuc19zZncgImh0dHA6Ly9ucy5hZG9iZS5jb20vU2F2ZUZvcldlYi8xLjAvIj4NCgk8IUVOVElUWSBuc19jdXN0b20gImh0dHA6Ly9ucy5hZG9iZS5jb20vR2VuZXJpY0N1c3RvbU5hbWVzcGFjZS8xLjAvIj4NCgk8IUVOVElUWSBuc19hZG9iZV94cGF0aCAiaHR0cDovL25zLmFkb2JlLmNvbS9YUGF0aC8xLjAvIj4NCl0+DQo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkViZW5lXzEiIHhtbG5zOng9IiZuc19leHRlbmQ7IiB4bWxuczppPSImbnNfYWk7IiB4bWxuczpncmFwaD0iJm5zX2dyYXBoczsiDQoJIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iMTI4cHgiIGhlaWdodD0iMTI4cHgiDQoJIHZpZXdCb3g9IjAgMCAxMjggMTI4IiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAxMjggMTI4IiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxtZXRhZGF0YT4NCgk8c2Z3ICB4bWxucz0iJm5zX3NmdzsiPg0KCQk8c2xpY2VzPjwvc2xpY2VzPg0KCQk8c2xpY2VTb3VyY2VCb3VuZHMgIGhlaWdodD0iMTI3Ljk4MyIgd2lkdGg9IjcyLjQyNCIgYm90dG9tTGVmdE9yaWdpbj0idHJ1ZSIgeD0iMjcuMzk2IiB5PSIwLjUwNSI+PC9zbGljZVNvdXJjZUJvdW5kcz4NCgk8L3Nmdz4NCjwvbWV0YWRhdGE+DQo8cGF0aCBmaWxsPSIjRkZGRkZGIiBkPSJNOTIuMjczLDEyNy45OTVIMzUuOTQzYy00LjQ0NCwwLTguMDQ3LTMuNTgxLTguMDQ3LTcuOTk5VjguMDExYzAtNC40MTcsMy42MDMtNy45OTksOC4wNDctNy45OTloNTYuMzMxDQoJYzQuNDQzLDAsOC4wNDcsMy41ODIsOC4wNDcsNy45OTl2MTExLjk4NUMxMDAuMzIsMTI0LjQxNCw5Ni43MTgsMTI3Ljk5NSw5Mi4yNzMsMTI3Ljk5NXogTTYzLjYwNSwxMTEuOTk2DQoJYzEzLjMzMywwLDI0LjE0MS0xMC43NDMsMjQuMTQxLTIzLjk5N2MwLTEzLjI1MS0xMC44MDktMjMuOTk1LTI0LjE0MS0yMy45OTVjLTEzLjMzMywwLTI0LjE0MSwxMC43NDQtMjQuMTQxLDIzLjk5NQ0KCUMzOS40NjQsMTAxLjI1Myw1MC4yNzMsMTExLjk5Niw2My42MDUsMTExLjk5NnogTTkyLjI3Myw4LjAxMUgzNS45NDN2NDcuOTkzaDU2LjMzMVY4LjAxMUw5Mi4yNzMsOC4wMTF6IE02My42MDUsNzkuMjQ2DQoJYzQuODY0LDAsOC44MDYsMy45Miw4LjgwNiw4Ljc1M2MwLDQuODM2LTMuOTQsOC43NTUtOC44MDYsOC43NTVjLTQuODY0LDAtOC44MDctMy45MTktOC44MDctOC43NTUNCglDNTQuNzk5LDgzLjE2Niw1OC43NDIsNzkuMjQ2LDYzLjYwNSw3OS4yNDZ6Ii8+DQo8cGF0aCBmaWxsPSIjRkZGRkZGIiBkPSJNNjMuOTkyLDIyLjk3MmM1LjAzMy0xMS4yNSwyMC4yOTktOS4wOTgsMjAuMzk4LDQuNTM0YzAuMDU3LDcuODA5LTIwLjM2OSwyMS44NzEtMjAuMzY5LDIxLjg3MQ0KCXMtMjAuNDctMTMuOTI5LTIwLjQxMy0yMS43ODlDNDMuNzA4LDEzLjk4OCw1OC43MTIsMTEuMjUzLDYzLjk5MiwyMi45NzJ6Ii8+DQo8L3N2Zz4NCg=='
			/* $position   */
		);

		new \Podlove\Modules\Networks\Settings\Dashboard( \Podlove\Podcast_Post_Type::NETWORK_SETTINGS_PAGE_HANDLE );
		new \Podlove\Modules\Networks\Settings\PodcastLists( \Podlove\Podcast_Post_Type::NETWORK_SETTINGS_PAGE_HANDLE );
		new \Podlove\Modules\Networks\Settings\Templates( \Podlove\Podcast_Post_Type::NETWORK_SETTINGS_PAGE_HANDLE );
		
		do_action( 'podlove_register_settings_pages', \Podlove\Podcast_Post_Type::NETWORK_SETTINGS_PAGE_HANDLE );
	}

	public function scripts_and_styles() {
		wp_register_style(
		    		'podlove_network_admin_style',
		    		\Podlove\PLUGIN_URL . '/lib/modules/networks/css/admin.css',
		    		false,
		    		\Podlove\get_plugin_header( 'Version' )
		    	);
		wp_enqueue_style('podlove_network_admin_style');
	}

}