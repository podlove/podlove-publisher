<?php 
namespace Podlove\Modules\PodloveWebPlayer;

class Podlove_Web_Player extends \Podlove\Modules\Base {

	protected $module_name = 'Podlove Web Player';
	protected $module_description = 'An audio player for the web. Let users listen to your podcast right on your website';

	public function load() {

		add_action( 'podlove_dashboard_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_filter( 'the_content', array( $this, 'autoinsert_into_content' ) );

		if ( defined( 'PODLOVEWEBPLAYER_DIR' ) ) {
			define( 'PODLOVE_MEDIA_PLAYER', 'external' );
			return;
		} else {
			define( 'PODLOVE_MEDIA_PLAYER', 'internal' );
		}

		include_once 'player/podlove-web-player/podlove-web-player.php';
	}

	public function autoinsert_into_content( $content ) {

		if ( get_post_type() !== 'podcast' )
			return $content;

		if ( self::there_is_a_player_in_the_content( $content ) )
			return $content;

		$inject = \Podlove\get_webplayer_setting( 'inject' );

		if ( $inject == 'beginning' ) {
			$content = '[podlove-web-player]' . $content;
		} elseif ( $inject == 'end' ) {
			$content = $content . '[podlove-web-player]';
		}

		return $content;
	}

	public function register_meta_boxes() {
		add_meta_box(
			\Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE . '_player',
			__( 'Webplayer', 'podlove' ),
			array( $this, 'about_player_meta_box' ),
			\Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE,
			'side'
		);
	}

	public function about_player_meta_box() {
		if ( PODLOVE_MEDIA_PLAYER === 'external' )
			echo __( 'It looks like you have installed an <strong>external plugin</strong> using mediaelement.js.<br>That\'s what\'s used.', 'podlove' );
		else
			echo __( 'Podlove ships with its <strong>own webplayer</strong>.<br>That\'s what\'s used.', 'podlove' );
	}

	public static function there_is_a_player_in_the_content( $content ) {
		return (
			stripos( $content, '[podloveaudio' ) !== false OR 
			stripos( $content, '[podlovevideo' ) !== false OR
			stripos( $content, '[audio' ) !== false OR 
			stripos( $content, '[video' ) !== false OR
			stripos( $content, '[podlove-web-player' ) !== false
		);
	}

}

