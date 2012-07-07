<?php 
namespace Podlove\Modules\PodloveWebPlayer;

class Podlove_Web_Player extends \Podlove\Modules\Base {

	private $module_name = 'Podlove Web Player';
	private $module_description = 'An audio player for the web. Let users listen to your podcast right on your website';

	public function load() {

		add_action( 'podlove_dashboard_meta_boxes', array( $this, 'register_meta_boxes' ) );

		if ( defined( 'PODLOVEWEBPLAYER_DIR' ) ) {
			define( 'PODLOVE_MEDIA_PLAYER', 'external' );
			return;
		} else {
			define( 'PODLOVE_MEDIA_PLAYER', 'internal' );
		}

		require_once 'player/podlove-web-player/podlove-web-player.php';
	}

	public function register_meta_boxes() {
		add_meta_box(
			\Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE . '_player',
			\Podlove\t( 'Webplayer' ),
			array( $this, 'about_player_meta_box' ),
			\Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE,
			'side'
		);
	}

	public function about_player_meta_box() {
		if ( PODLOVE_MEDIA_PLAYER === 'external' )
			echo \Podlove\t( 'It looks like you have installed an <strong>external plugin</strong> using mediaelement.js.<br>That\'s what\'s used.' );
		else
			echo \Podlove\t( 'Podlove ships with its <strong>own webplayer</strong>.<br>That\'s what\'s used.' );
	}

}

