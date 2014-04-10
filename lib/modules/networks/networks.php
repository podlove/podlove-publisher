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
			/* $function   */ function () { /* see \Podlove\Settings\Dashboard */ }
			/* $icon_url   */ // \Podlove\PLUGIN_URL . '/images/podlove/icon-adminmenu16-sprite.png'
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

		print_r(Network::all());

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