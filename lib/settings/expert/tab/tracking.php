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

				<script type="text/javascript">
				jQuery(function($) {
					function manage_tracking_setting_visibility() {
						var input = $("input[name='podlove_tracking[mode]']:checked"),
							value = input.val(),
							toggleElements = $("#enable_ips, #respect_dnt").closest("tr");

						if (value == "ptm_analytics") {
							toggleElements.show();
						} else {
							toggleElements.hide();
						}
					}

					$("input[name='podlove_tracking[mode]']").on("change", function(e) {
						manage_tracking_setting_visibility();
					});

					manage_tracking_setting_visibility();
				});
				</script>
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

		add_settings_field(
			/* $id       */ 'podlove_setting_tracking_ips',
			/* $title    */ sprintf(
				'<label id="enable_ips">%s</label>',
				__( 'Save IP addresses', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove_tracking[enable_ips]" type="radio" value="1" <?php checked( \Podlove\get_setting( 'tracking', 'enable_ips' ), 1 ) ?> /> <?php echo __( 'Save IP addresses (allows geo tracking).', 'podlove' ) ?>
				</label>
				<br>
				<label>
					<input name="podlove_tracking[enable_ips]" type="radio" value="0" <?php checked( \Podlove\get_setting( 'tracking', 'enable_ips' ), 0 ) ?> /> <?php echo __( 'Do not save IP addresses.', 'podlove' ) ?>
				</label>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_tracking_dnt',
			/* $title    */ sprintf(
				'<label id="respect_dnt">%s</label>',
				__( 'Respect DNT Header', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove_tracking[respect_dnt]" type="radio" value="1" <?php checked( \Podlove\get_setting( 'tracking', 'respect_dnt' ), 1 ) ?> /> <?php echo __( 'Respect DNT.', 'podlove' ) ?>
				</label>
				<br>
				<label>
					<input name="podlove_tracking[respect_dnt]" type="radio" value="0" <?php checked( \Podlove\get_setting( 'tracking', 'respect_dnt' ), 0 ) ?> /> <?php echo __( 'Ignore DNT.', 'podlove' ) ?>
				</label>
				<p>
					<?php echo sprintf(
						__('Respect the %sDO NOT TRACK-Header%s of clients. When it is set, neither IP nor user agent will be saved if the client sends a DNT header.', 'podlove'),
						'<a href="http://en.wikipedia.org/wiki/Do_not_track" target="_blank">',
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