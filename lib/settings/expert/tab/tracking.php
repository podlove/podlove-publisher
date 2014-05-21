<?php
namespace Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class Tracking extends Tab {

	public function init() {

		add_settings_section(
			/* $id 		 */ 'podlove_settings_episode',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Episode Tracking Settings', 'podlove' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_tracking_ptm',
			/* $title    */ sprintf(
				'<label for="enable_ptm">%s</label>',
				__( 'Enable PTM parameters in file URLs', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove_tracking[enable_ptm]" id="enable_ptm" type="radio" value="1" <?php checked( \Podlove\get_setting( 'tracking', 'enable_ptm' ), 1 ) ?> /> <?php echo __( 'enable', 'podlove' ) ?>
				</label>
				<br>
				<label>
					<input name="podlove_tracking[enable_ptm]" id="enable_ptm" type="radio" value="0" <?php checked( \Podlove\get_setting( 'tracking', 'enable_ptm' ), 0 ) ?> /> <?php echo __( 'disable', 'podlove' ) ?>
				</label>
				<p>
					<?php echo __('PTM is the "Podlove Tracking Module". If you enable it, file URLs are modified to include additional data like the download source (feed, website, etc.). This enables better analytics.', 'podlove') ?>
				</p>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_tracking_ips',
			/* $title    */ sprintf(
				'<label for="enable_ips">%s</label>',
				__( 'Save IPs', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove_tracking[enable_ips]" id="enable_ips" type="radio" value="1" <?php checked( \Podlove\get_setting( 'tracking', 'enable_ips' ), 1 ) ?> /> <?php echo __( 'save IPs', 'podlove' ) ?>
				</label>
				<br>
				<label>
					<input name="podlove_tracking[enable_ips]" id="enable_ips" type="radio" value="0" <?php checked( \Podlove\get_setting( 'tracking', 'enable_ips' ), 0 ) ?> /> <?php echo __( 'do not save IPs', 'podlove' ) ?>
				</label>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_tracking_dnt',
			/* $title    */ sprintf(
				'<label for="respect_dnt">%s</label>',
				__( 'Respect DNT Header', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove_tracking[respect_dnt]" id="respect_dnt" type="radio" value="1" <?php checked( \Podlove\get_setting( 'tracking', 'respect_dnt' ), 1 ) ?> /> <?php echo __( 'respect DNT', 'podlove' ) ?>
				</label>
				<br>
				<label>
					<input name="podlove_tracking[respect_dnt]" id="respect_dnt" type="radio" value="0" <?php checked( \Podlove\get_setting( 'tracking', 'respect_dnt' ), 0 ) ?> /> <?php echo __( 'ignore DNT', 'podlove' ) ?>
				</label>
				<p>
					<?php echo sprintf(
						__('Respect the %sDO NOT TRACK-Header%s of clients. When it is set, neither IP nor user agent will be saved if the client sends a DNT header.', 'podlove'),
						'<a href="https://www.eff.org/issues/do-not-track" target="_blank">',
						'</a>'
					); ?>
				</p>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		register_setting( Settings::$pagehook, 'podlove_tracking' );
	}

}