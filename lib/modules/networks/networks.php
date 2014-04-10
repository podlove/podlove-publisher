<?php 
namespace Podlove\Modules\Networks;

use \Podlove\Model;
use \Podlove\Modules\Networks\Model\Base;
use \Podlove\Modules\Networks\Model\Network;

class Networks extends \Podlove\Modules\Base {

	protected $module_name = 'Podcast Network';
	protected $module_description = 'Support for Podcast Networks using <a href="http://codex.wordpress.org/Create_A_Network">WordPress Multisite</a> environments.';
	protected $module_group = 'system';

	public function load() {
		// Actions after activation
		add_action( 'podlove_module_was_activated_networks', array( $this, 'was_activated' ) );

		// Adding Network Admin Menu
		add_action( 'network_admin_menu', array( $this, 'create_network_menu' ) );

		// Adding Shortcodes
		//add_shortcode( 'podlove-latest-network-episodes', array( $this, 'shortcode_latest_episodes' ) );
		//add_shortcode( 'podlove-network-podcasts', array( $this, 'shortcode_list_podcasts' ) );
	}

	/*
	 *	Was activated
	 */ 

	public function was_activated( $module_name ) {
		Network::build();
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
		new \Podlove\Modules\Networks\Settings\Networks( \Podlove\Podcast_Post_Type::NETWORK_SETTINGS_PAGE_HANDLE );
		
		do_action( 'podlove_register_settings_pages', \Podlove\Podcast_Post_Type::NETWORK_SETTINGS_PAGE_HANDLE );
	}

	/*
	 *  Shortcodes: Display latest Episodes and list all Podcasts in the network
	 */

	public function shortcode_latest_episodes() {

		$latest_episodes = \Podlove\Modules\Networks\Model\Network::latest_episodes();

		$source = " <table>
					<thead>
						<tr>
							<th></th>
							<th>Title</th>
							<th>Date</th>
							<th>Podcast</th>
						</tr>
					</thead>
					<tbody>";

		foreach ( $latest_episodes as $episode ) {
			switch_to_blog( $episode['blog_id'] );
			$podcast = \Podlove\Model\Podcast::get_instance();
			$post = get_post( $episode['episode']->post_id ); 

			$source = $source . "<tr>";
			$source = $source . "	<td><img src='" . $episode['episode']->get_cover_art_with_fallback() . "' alt='" . $episode['episode']->full_title() . "' style='width: 80px;' /></td>";
			$source = $source . "	<td>" . $episode['episode']->full_title() . "</td>";
			$source = $source . "	<td>" . $post->post_date . "</td>";
			$source = $source . "	<td>" . $podcast->title . "</td>";
			$source = $source . "</tr>";
		}

		$source = $source . "</tbody></table>"; 

		return $source;
	}

	public function shortcode_list_podcasts( $attributes ) {

		$podcasts = \Podlove\Modules\Networks\Model\Network::get_podcasts();

		$source = "<ul class='podlove_network_podcast_list'>";

		foreach ($podcasts as $blog_id => $podcast) {
			$source = $source . "<li><ul>";
			$source = $source . "	<li><h2>" . $podcast->title . "</h2></li>";
			$source = $source . "	<li class='cover'><img src='" . $podcast->cover_image . "' alt='" . $podcast->title . "' /></li>";
			$source = $source . "	<li>" . $podcast->summary . "</li>";
			$source = $source . "</ul></li>";
		}

		return $source . "</ul>";

	}

}