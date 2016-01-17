<?php
namespace Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;
use \Podlove\Model;

class WebPlayer extends Tab {
	public function init() {

		add_settings_section(
			/* $id 		 */ 'podlove_settings_episode',
			/* $title 	 */ __( '', 'podlove-podcasting-plugin-for-wordpress' ),	
			/* $callback */ function () { echo '<h3>' . __( 'WebPlayer Settings', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);

		register_setting( Settings::$pagehook, 'podlove_webplayer_formats' );
		register_setting( Settings::$pagehook, 'podlove_webplayer_settings', function($setting) {

			\Podlove\Cache\TemplateCache::get_instance()->setup_purge();

			return $setting;
		} );
	}

	public function page() {
		?>
		<form method="post" action="options.php">
			<?php if ( isset( $_REQUEST['podlove_tab'] ) ): ?>
				<input type="hidden" name="podlove_tab" value="<?php echo $_REQUEST['podlove_tab'] ?>" />
			<?php endif; ?>
			<?php settings_fields( Settings::$pagehook ); ?>
			<?php do_settings_sections( Settings::$pagehook ); ?>

			<?php echo __( 'Webplayers are able to provide various media formats depending on context. Try to provide as many as possible to maximize compatibility with all browsers.', 'podlove-podcasting-plugin-for-wordpress' ); ?>

			<table class="form-table">
				<?php $this->form_fields(); ?>
			</table>
			
			<?php submit_button( __( 'Save Changes' ), 'button-primary', 'submit', TRUE ); ?>
		</form>
		<?php
	}

	/**
	 * Config array containing list of supported web player assets.
	 * Each type (audio, video) lists supported extensions with their title and mime_type.
	 * 
	 * @return array
	 */
	public static function formats() {
		return array(
			'audio' => array(
				'mp3' => array(
					'title'     => __( 'MP3 Audio', 'podlove-podcasting-plugin-for-wordpress' ),
					'mime_types' => array('audio/mpeg')
				),
				'mp4' => array(
					'title'     => __( 'MP4 Audio', 'podlove-podcasting-plugin-for-wordpress' ),
					'mime_types' => array('audio/mp4')
				),
				'ogg' => array(
					'title'     => __( 'OGG Audio', 'podlove-podcasting-plugin-for-wordpress' ),
					'mime_types' => array('audio/ogg')
				),
				'opus' => array(
					'title'     => __( 'Opus Audio', 'podlove-podcasting-plugin-for-wordpress' ),
					'mime_types' => array('audio/ogg;codecs=opus', 'audio/opus')
				),
			),
			'video' => array(
				'mp4'  => array(
					'title'     => __( 'MP4 Video', 'podlove-podcasting-plugin-for-wordpress' ),
					'mime_types' => array('video/mp4')
				),
				'ogg'  => array(
					'title'     => __( 'OGG Video', 'podlove-podcasting-plugin-for-wordpress' ),
					'mime_types' => array('video/ogg')
				),
				'webm' => array(
					'title'     => __( 'Webm Video', 'podlove-podcasting-plugin-for-wordpress' ),
					'mime_types' => array('video/webm')
				),
			)
		);
	}

	public function form_fields() {

		$formats_data = get_option( 'podlove_webplayer_formats', array() );
		$episode_assets = Model\EpisodeAsset::all();

		foreach ( self::formats() as $format => $extensions ) {
			?>
			<tr valign="top">
				<th scope="row" valign="top" colspan="2">
					<h3><?php echo ucfirst( $format ); ?></h3>
				</th>
			</tr>
			<?php
			foreach ( $extensions as $extension => $extension_data ) {
				$label = $extension_data['title'];
				$mime_types = $extension_data['mime_types'];

				$id    = sprintf( 'podlove_webplayer_formats_%s_%s'  , $format, $extension );
				$name  = sprintf( 'podlove_webplayer_formats[%s][%s]', $format, $extension );
				$value = ( isset( $formats_data[$format] ) && isset( $formats_data[$format][$extension] ) ) ? $formats_data[$format][$extension] : 0;
				?>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="<?php echo $id ?>"><?php echo $label; ?></label>
					</th>
					<td>
						<div>
							<select name="<?php echo $name; ?>" id="<?php echo $id; ?>">
								<option value="0" <?php selected( 0, $value ); ?> ><?php echo __( 'Unused', 'podlove-podcasting-plugin-for-wordpress' ); ?></option>
								<?php foreach ( $episode_assets as $episode_asset ): ?>
									<?php $file_type = $episode_asset->file_type(); ?>
									<?php if ( $file_type && in_array($file_type->mime_type, $mime_types) ): ?>
										<option value="<?php echo $episode_asset->id; ?>" <?php selected( $episode_asset->id, $value ); ?>><?php echo $episode_asset->title ?></option>
									<?php endif ?>
								<?php endforeach ?>
							</select>
						</div>
					</td>
				</tr>
				<?php 
			}
		}

		// advanced settings

		$theme_options = [];
		$player_css_dir = \Podlove\PLUGIN_DIR . 'lib/modules/podlove_web_player/player_v3/css/';
		$dir = new \DirectoryIterator($player_css_dir);
		foreach ($dir as $fileinfo) {
			if ($fileinfo->getExtension() == 'css') {
				$filename = $fileinfo->getFilename();
				$filetitle = str_replace(".css", "", $filename);
				$filetitle = str_replace(".min", "", $filetitle);
				$filetitle = str_replace("-", " ", $filetitle);
				$filetitle = str_replace("pwp", "PWP", $filetitle);
				$theme_options[$filename] = $filetitle;
			}
		}

		$settings = array(
			'inject' => array(
				'label'       => __( 'Insert player automatically', 'podlove-podcasting-plugin-for-wordpress' ),
				'description' => __( 'Automatically insert web player shortcode at beginning or end of an episode. Alternatvely, use the shortcode <code>[podlove-episode-web-player]</code>.', 'podlove-podcasting-plugin-for-wordpress' ),
				'options'     => array(
					'manually'  => __( 'insert manually via shortcode', 'podlove-podcasting-plugin-for-wordpress' ),
					'beginning' => __( 'insert at the beginning', 'podlove-podcasting-plugin-for-wordpress' ),
					'end'       => __( 'insert at the end', 'podlove-podcasting-plugin-for-wordpress' )
				)
			),
			'chaptersVisible'     => array(
				'label'   => __( 'Chapters Visibility', 'podlove-podcasting-plugin-for-wordpress' ),
				'options' => array(
					'true' => __( 'Visible when player loads', 'podlove-podcasting-plugin-for-wordpress' ),
					'false' => __( 'Hidden when player loads', 'podlove-podcasting-plugin-for-wordpress' )
				)
			),
			'version'     => array(
				'label'   => __( 'Player Version', 'podlove-podcasting-plugin-for-wordpress' ),
				'options' => array(
					'player_v2' => __( 'Podlove Web Player 2', 'podlove-podcasting-plugin-for-wordpress' ),
					'player_v3' => __( 'Podlove Web Player 3 (unstable beta, don\'t use in production)', 'podlove-podcasting-plugin-for-wordpress' )
				)
			),
			'playerv3theme' => [
				'label' => 'Web Player Theme',
				'description' => 'For Web Player V3 only.',
				'options' => $theme_options
			]
		);

		?>
		<tr valign="top">
			<th scope="row" valign="top" colspan="2">
				<h3><?php echo __( 'Settings', 'podlove-podcasting-plugin-for-wordpress' ); ?></h3>
			</th>
		</tr>
		<?php foreach ( $settings as $setting_key => $field_values ): ?>
			<tr class="row_<?php echo $setting_key; ?>">
				<th scope="row" valign="top">
					<?php if ( isset( $field_values['label'] ) && $field_values['label'] ): ?>
						<label for="<?php echo $setting_key; ?>"><?php echo $field_values['label']; ?></label>
					<?php endif ?>
				</th>
				<td>
					<select name="podlove_webplayer_settings[<?php echo $setting_key; ?>]" id="<?php echo $setting_key; ?>">
						<?php foreach ( $field_values['options'] as $key => $value ): ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php if ( $key == \Podlove\get_webplayer_setting( $setting_key ) ): ?> selected="selected"<?php endif; ?>><?php echo $value; ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( isset( $field_values['description'] ) &&  $field_values['description'] ): ?>
						<div class="description"><?php echo $field_values['description']; ?></div>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php
	}

}