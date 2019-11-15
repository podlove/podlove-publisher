<?php
namespace Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;
use \Podlove\Model;
use \Podlove\Geo_Ip;

class Tracking extends Tab {

	public function get_slug() {
		return 'tracking';
	}	

	public function init() {

		add_settings_section(
			/* $id 		 */ 'podlove_settings_episode',
			/* $title 	 */ __( '', 'podlove-podcasting-plugin-for-wordpress' ),	
			/* $callback */ function () {
				echo '<h3>' . __( 'Download Tracking & Analytics Settings', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>';
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
				__( 'Tracking Mode', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>

				<label class="aligned-radio">
					<div class="row">
						<div>
							<input name="podlove_tracking[mode]" type="radio" value="ptm_analytics" <?php checked( \Podlove\get_setting( 'tracking', 'mode' ), 'ptm_analytics' ) ?> />
						</div>
						<div>
							<?php echo sprintf(
								'<div><strong>%s</strong><br>%s</div>',
								__( 'Tracking URL Parameters &amp; Analytics', 'podlove-podcasting-plugin-for-wordpress' ),
								__( 'Instead of the original file URLs, users and clients see a link that points to Podlove Publisher. 
									Podlove Publisher logs the download intent and redirects the user to the original file. 
									That way Podlove Publisher is able to generate download statistics. ', 'podlove-podcasting-plugin-for-wordpress' )
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
								__( 'Tracking URL Parameters', 'podlove-podcasting-plugin-for-wordpress' ),
								__( 'Original file URLs are extended by tracking parameters before presenting them to users and clients. 
									This is useful if you are using your server log files for download analytics. 
									No download-data is tracked.', 'podlove-podcasting-plugin-for-wordpress' )
							); ?>
						</div>
					</div>
				</label>

				<label class="aligned-radio">
					<div class="row">
						<div>
							<input name="podlove_tracking[mode]" type="radio" value="0" <?php checked( \Podlove\get_setting( 'tracking', 'mode' ), 0 ) ?> />
						</div>
						<div>
							<?php echo sprintf(
								'<div><strong>%s</strong><br>%s</div>',
								__( 'No Tracking', 'podlove-podcasting-plugin-for-wordpress' ),
								__( 'Original file URLs are presented to users and clients. No download-data is tracked.', 'podlove-podcasting-plugin-for-wordpress' )
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
			/* $id       */ 'podlove_setting_tracking_window',
			/* $title    */ sprintf(
				'<label for="mode">%s</label>',
				__( 'Deduplication Window', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>

				<p class="description" style="margin-bottom: 15px;">
				  <?php echo sprintf(
						__('A requests counts as identical when the same IP and user agent are used to access the same file in a certain time window.
					Podlove Publisher has traditionally used an hourly time window but IAB recommends daily. Beware: Once changed you need to 
					do a full Download Intent Cleanup and Download Aggregation for the change to take effect. Do this at the %stools page%s.'),
						'<a href="' . admin_url('admin.php?page=podlove_tools_settings_handle#the_tools_section') . '">',
						'</a>'
					); ?>
				</p>

				<label class="aligned-radio">
					<div class="row">
						<div>
							<input name="podlove_tracking[window]" type="radio" value="hourly" <?php checked( \Podlove\get_setting( 'tracking', 'window' ), 'hourly' ) ?> />
						</div>
						<div>
							<?php echo sprintf(
								'<div><strong>%s</strong><br>%s</div>',
								__( 'Hour', 'podlove-podcasting-plugin-for-wordpress' ),
								__( 'Identical requests during the same hour are counted once.', 'podlove-podcasting-plugin-for-wordpress' )
							); ?>
						</div>
					</div>
				</label>

				<label class="aligned-radio">
					<div class="row">
						<div>
							<input name="podlove_tracking[window]" type="radio" value="daily" <?php checked( \Podlove\get_setting( 'tracking', 'window' ), 'daily' ) ?> />
						</div>
						<div>
							<?php echo sprintf(
								'<div><strong>%s</strong><br>%s</div>',
								__( 'Day', 'podlove-podcasting-plugin-for-wordpress' ),
								__( 'Identical requests during the same day are counted once.', 'podlove-podcasting-plugin-for-wordpress' )
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
			/* $id       */ 'podlove_setting_tracking_google_analytics',
			/* $title    */ sprintf(
				'<label for="mode">%s</label>',
				__( 'Google Analytics Tracking ID', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>

				<div>
				  <input class="large-text" type="text" name="podlove_tracking[ga]" value="<?php echo(\Podlove\get_setting( 'tracking', 'ga' )) ?> " />
				</div>
				<div> 
				<?php 
				echo __( 'Google Analytics Tracking ID. If entered, Podlove Publisher will log download intents to GA. Leave blank to deactivate GA reporting.', 'podlove-podcasting-plugin-for-wordpress' );
				?>
				</div>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_status_location_database',
			/* $title    */ sprintf(
				'<label for="mode">%s</label>',
				__( 'Geolocation Lookup', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				$file = \Podlove\Geo_Ip::get_upload_file_path();
				\Podlove\Geo_Ip::register_updater_cron();
				?>
				<?php if (!class_exists('PharData')): ?>
					<?php echo __('Required PHP class <code>PharData</code> is missing.', 'podlove-podcasting-plugin-for-wordpress') ?>
				<?php elseif ( file_exists($file) ): ?>
					<p>
						<?php echo __("Geolocation database", 'podlove-podcasting-plugin-for-wordpress'); ?>:
						<code><?php echo $file ?></code>
					</p>
					<p>
						<?php echo __("Last modified", 'podlove-podcasting-plugin-for-wordpress'); ?>: 
						<?php echo date(get_option('date_format') . ' ' . get_option( 'time_format' ), filemtime($file)) ?>
					</p>
					<p>
						<?php echo sprintf(
							__("The database is updated automatically once a month. Next scheduled update: %s", 'podlove-podcasting-plugin-for-wordpress'),
							date(get_option('date_format') . ' ' . get_option( 'time_format' ), wp_next_scheduled('podlove_geoip_db_update'))
						) ?>
					</p>
					<p>
						<button name="update_geo_database" class="button button-primary" value="1"><?php echo __("Update Now", 'podlove-podcasting-plugin-for-wordpress') ?></button>
					</p>
				<?php else: ?>
					<p>
						<?php echo __("You need to download a geolocation-database for lookups to work.", 'podlove-podcasting-plugin-for-wordpress') ?>
					</p>
					<p>
						<button name="update_geo_database" class="button button-primary" value="1"><?php echo __("Download Now", 'podlove-podcasting-plugin-for-wordpress') ?></button>
					</p>
				<?php endif; ?>
				<p>
					<?php echo sprintf(__('Geo-Tracking is <em>%s</em>.', 'podlove-podcasting-plugin-for-wordpress'), Geo_ip::is_enabled() ? __('active', 'podlove-podcasting-plugin-for-wordpress') : __('inactive', 'podlove-podcasting-plugin-for-wordpress')); ?>
				</p>
				<p>
					<!-- This snippet must be included, as stated here: http://dev.maxmind.com/geoip/geoip2/geolite2/ -->
					<em>
						<?php echo sprintf(
							__('This product includes GeoLite2 data created by MaxMind, available from %s.', 'podlove-podcasting-plugin-for-wordpress'),
							'<a href="http://www.maxmind.com">http://www.maxmind.com</a>'
						) ?>
					</em>
				</p>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		add_settings_field(
			/* $id       */ 'podlove_debug_tracking',
			/* $title    */ sprintf(
				'<label for="mode">%s</label>',
				__( 'Debug Tracking', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {

				if (!\get_option('permalink_structure')) {
					?>
					<div class="error">
						<p>
							<b><?php echo __('Please Change Permalink Structure', 'podlove-podcasting-plugin-for-wordpress') ?></b>
							<?php
							echo sprintf(
								__('You are using the default WordPress permalink structure. 
								This may cause problems with some podcast clients when you activate tracking.
								Go to %s and set it to anything but default (for example "Post name") before activating Tracking.', 'podlove-podcasting-plugin-for-wordpress'),
								'<a href="' . admin_url('options-permalink.php') . '">' . __('Permalink Settings') . '</a>'
							);
							?>
						</p>
					</div>
					<?php
				}

				$media_file = Model\MediaFile::find_example();
				if (!$media_file)
					return;

				$episode = $media_file->episode();
				if (!$episode)
					return;

				$public_url = $media_file->get_public_file_url("debug");
				$actual_url = $media_file->get_file_url(); 

				?>
				<h4><?php __('Example Episode', 'podlove-podcasting-plugin-for-wordpress') ?></h4>
				<p>
					<?php echo $episode->full_title() ?>
				</p>
				<h4><?php __('Media File', 'podlove-podcasting-plugin-for-wordpress') ?></h4>
				<p>
					<h5><?php __('Actual Location', 'podlove-podcasting-plugin-for-wordpress') ?></h5>
					<code><?php echo $actual_url ?></code>
				</p>
				<p>
					<h5><?php __('Public URL', 'podlove-podcasting-plugin-for-wordpress') ?></h5>
					<code><?php echo $public_url ?></code>
				</p>
				<p>
					<h5><?php __('Validations', 'podlove-podcasting-plugin-for-wordpress') ?></h5>
					<ul>
						<li>
							<!-- check rewrite rules -->
							<?php if ( \Podlove\Tracking\Debug::rewrites_exist() ): ?>
								✔ <?php _e('Rewrite Rules Exist', 'podlove-podcasting-plugin-for-wordpress') ?>
							<?php else: ?>
								✘ <strong><?php _e('Rewrite Rules Missing', 'podlove-podcasting-plugin-for-wordpress') ?></strong>
								<!-- todo: repair button -->
							<?php endif; ?>
						</li>
						<li>
							<?php if ( \Podlove\Tracking\Debug::url_resolves_correctly($public_url, $actual_url) ): ?>
								✔ <?php _e('URL resolves correctly', 'podlove-podcasting-plugin-for-wordpress') ?>
							<?php else: ?>
								✘ <strong><?php _e('URL does not resolve correctly', 'podlove-podcasting-plugin-for-wordpress') ?></strong>
								<?php if (stristr($actual_url, 'https') !== false && \Podlove\get_setting('website', 'ssl_verify_peer') == 'on'): ?>
									<em><?php echo sprintf(__('The cause might be a server specific SSL misconfiguration. To work around this, disable "Check for Assets with SSL-peer-verification" in %sExpert Settings%s or ask your admin/hoster for help.', 'podlove-podcasting-plugin-for-wordpress'), '<a href="' . admin_url('admin.php?page=podlove_settings_settings_handle') . '" target="_blank">', '</a>') ?></em>
								<?php endif ?>
							<?php endif; ?>
						</li>
						<li>
							<!-- check http/https consistency -->
							<?php if ( \Podlove\Tracking\Debug::is_consistent_https_chain($public_url, $actual_url) ): ?>
								✔ <?php _e('Consistent protocol chain', 'podlove-podcasting-plugin-for-wordpress') ?>
							<?php else: ?>
								✘ <strong><?php _e('Protocol chain is inconsistent', 'podlove-podcasting-plugin-for-wordpress') ?></strong>: <?php _e('Your site uses SSL but the files are not served with SSL. Many clients will not allow to download episodes. To fix this, serve files via SSL or deactivate tracking.', 'podlove-podcasting-plugin-for-wordpress') ?>
							<?php endif; ?>
						</li>
						<li>
							<?php if (Geo_Ip::is_db_valid()): ?>
								✔ <?php _e('Geolocation database valid', 'podlove-podcasting-plugin-for-wordpress') ?>
								<?php Geo_Ip::enable_tracking(); ?>
							<?php else: ?>
								<?php Geo_Ip::disable_tracking(); ?>
								✘ <strong><?php _e('Geolocation database invalid or outdated', 'podlove-podcasting-plugin-for-wordpress') ?></strong>:
								<?php echo sprintf(
									__('Try updating it using the button above. If that doesn\'t work, delete it manually: %s, then redownload it in the section above. If that fails, you can download it with your web browser, unzip it, and upload it to WordPress using sFTP: %s', 'podlove-podcasting-plugin-for-wordpress'),
									'<code>' . esc_html(Geo_Ip::get_upload_file_path()) . '</code>',
									'<a href="' . esc_url(Geo_Ip::SOURCE_URL) . '" download>' . esc_html(Geo_Ip::SOURCE_URL) . '</a>'
								) ?>
							<?php endif ?>
						</li>
					</ul>
				</p>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		register_setting(
			Settings::$pagehook,
			'podlove_tracking',
			function($args)
			{
				if (isset($_REQUEST['update_geo_database']))
					\Podlove\Geo_Ip::update_database();

				\Podlove\Cache\TemplateCache::get_instance()->setup_purge();

				return $args;
			}
		);
	}

}
