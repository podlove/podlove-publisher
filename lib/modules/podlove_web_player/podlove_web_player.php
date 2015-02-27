<?php 
namespace Podlove\Modules\PodloveWebPlayer;

use Podlove\Model\Episode;

class Podlove_Web_Player extends \Podlove\Modules\Base {

	protected $module_name = 'Podlove Web Player';
	protected $module_description = 'An audio player for the web. Let users listen to your podcast right on your website';
	protected $module_group = 'web publishing';

	public function load() {

		add_filter( 'the_content', array( $this, 'autoinsert_into_content' ) );

		add_action('wp_footer', [$this, 'playerFooter'] );
		add_action('wp_head', [$this, 'playerHeader'] );
	}

	public function playerFooter() {
		?>
		<script src="<?php echo \Podlove\PLUGIN_URL ?>/bower_components/podlove-web-player/dist/js/podlove-web-moderator.min.js"></script>
		<script>
			jQuery("audio").podlovewebplayer({
				staticEmbedPage: "<?php echo \Podlove\PLUGIN_URL ?>/bower_components/podlove-web-player/dist/static.html"
			});
		</script>
		<?php
	}

	public function playerHeader() {
		?>
		<script>var pwp_metadata = {};</script>
		<?php
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
