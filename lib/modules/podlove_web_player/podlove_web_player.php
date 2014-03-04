<?php 
namespace Podlove\Modules\PodloveWebPlayer;

use Podlove\Model\Episode;

class Podlove_Web_Player extends \Podlove\Modules\Base {

	protected $module_name = 'Podlove Web Player';
	protected $module_description = 'An audio player for the web. Let users listen to your podcast right on your website';
	protected $module_group = 'web publishing';

	public function load() {

		add_action( 'podlove_dashboard_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_filter( 'the_content', array( $this, 'autoinsert_into_content' ) );
		add_action('wp', array( $this, 'standalone_player_page' ) );

		if ( defined( 'PODLOVEWEBPLAYER_DIR' ) ) {
			define( 'PODLOVE_MEDIA_PLAYER', 'external' );
			return;
		} else {
			define( 'PODLOVE_MEDIA_PLAYER', 'internal' );
		}

		include_once 'player/podlove-web-player/podlove-web-player.php';
	}

	public function standalone_player_page() {

		if (!isset($_GET['standalonePlayer']))
			return;

		if (!is_single())
			return;

		if (!$episode = Episode::find_or_create_by_post_id(get_the_ID()))
			return;

		?>
<!DOCTYPE html>
    <head>
        <script type="text/javascript" src="<?php echo $this->get_module_url() ?>/js/html5shiv.js"></script>
        <script type="text/javascript" src="<?php echo $this->get_module_url() ?>/js/jquery-1.9.1.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->get_module_url() ?>/player/podlove-web-player/static/podlove-web-player.js"></script>
        <link rel="stylesheet" href="<?php echo $this->get_module_url() ?>/player/podlove-web-player/static/podlove-web-player.css" />
    </head>
    <body>
	    <?php
	    $printer = new Printer($episode);
	    echo $printer->render();
	    ?>
    </body>
</html>
		<?php
		exit;
	}

	public function autoinsert_into_content( $content ) {

		if ( get_post_type() !== 'podcast' || post_password_required() )
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
			stripos( $content, '[podlove-web-player' ) !== false OR
			stripos( $content, '[podlove-template' ) !== false
		);
	}

}

