<?php
namespace Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class Tracking extends Tab {

	public function init() {

		add_settings_section(
			/* $id 		 */ 'podlove_settings_episode',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () {
				echo '<h3>' . __( 'Download Tracking & Analytics Settings', 'podlove' ) . '</h3>';
				?>
				<style type="text/css">
				.form-table .aligned-radio { display: table; margin-bottom: 10px; }
				.form-table .aligned-radio .row { display: table-row; }
				.form-table .aligned-radio .row > div { display: table-cell; }
				</style>
				<?php
			},
			/* $page	 */ Settings::$pagehook	
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_tracking',
			/* $title    */ sprintf(
				'<label for="mode">%s</label>',
				__( 'Tracking Mode', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label class="aligned-radio">
					<div class="row">
						<div>
							<input name="podlove_tracking[mode]" type="radio" value="0" <?php checked( \Podlove\get_setting( 'tracking', 'mode' ), 0 ) ?> />
						</div>
						<div>
							<?php echo sprintf(
								'<div><strong>%s</strong><br>%s</div>',
								__( 'No Tracking', 'podlove' ),
								__( 'Original file URLs are presented to users and clients. No download-data is tracked.', 'podlove' )
							); ?>
						</div>
					</div>
				</label>
				
				<label class="aligned-radio">
					<div class="row">
						<div>
							<input name="podlove_tracking[mode]" type="radio" value="ptm" <?php checked( \Podlove\get_setting( 'tracking', 'mode' ), 'ptm' ) ?> />
						</div>
						<div>
							<?php echo sprintf(
								'<div><strong>%s</strong><br>%s</div>',
								__( 'Tracking URL Parameters', 'podlove' ),
								__( 'Original file URLs are extended by tracking parameters before presenting them to users and clients. 
									This is useful if you are using your server log files for download analytics. 
									No download-data is tracked.', 'podlove' )
							); ?>
						</div>
					</div>
				</label>

				<label class="aligned-radio">
					<div class="row">
						<div>
							<input name="podlove_tracking[mode]" type="radio" value="ptm_analytics" <?php checked( \Podlove\get_setting( 'tracking', 'mode' ), 'ptm_analytics' ) ?> />
						</div>
						<div>
							<?php echo sprintf(
								'<div><strong>%s</strong><br>%s</div>',
								__( 'Tracking URL Parameters &amp; Analytics', 'podlove' ),
								__( 'Instead of the original file URLs, users and clients see a link that points to the Publisher. 
									The Publisher logs the download intent and redirects the user to the original file. 
									That way the Publisher is able to generate download statistics. ', 'podlove' )
							); ?>
						</div>
					</div>
				</label>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		register_setting( Settings::$pagehook, 'podlove_tracking' );
	}

}