<?php
namespace Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class Website extends Tab {
	public function init() {
		add_settings_section(
			/* $id 		 */ 'podlove_settings_general',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Website Settings', 'podlove' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_merge_episodes',
			/* $title    */ sprintf(
				'<label for="merge_episodes">%s</label>',
				__( 'Display episodes on front page together with blog posts', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_website[merge_episodes]" id="merge_episodes" type="checkbox" <?php checked( \Podlove\get_setting( 'website', 'merge_episodes' ), 'on' ) ?>>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_hide_wp_feed_discovery',
			/* $title    */ sprintf(
				'<label for="hide_wp_feed_discovery">%s</label>',
				__( 'Hide default WordPress Feeds for blog and comments (no auto-discovery).', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_website[hide_wp_feed_discovery]" id="hide_wp_feed_discovery" type="checkbox" <?php checked( \Podlove\get_setting( 'website', 'hide_wp_feed_discovery' ), 'on' ) ?>>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_custom_episode_slug',
			/* $title    */ sprintf(
				'<label for="custom_episode_slug">%s</label>',
				__( 'Permalink structure for episodes', 'podlove' )
			),
			/* $callback */ function () {

				$use_post_permastruct = \Podlove\get_setting( 'website', 'use_post_permastruct' );
				$custom_episode_slug  = \Podlove\get_setting( 'website', 'custom_episode_slug' );

				if ( $blog_prefix = \Podlove\get_blog_prefix() ) {
					$custom_episode_slug = preg_replace( '|^/?blog|', '', $custom_episode_slug );
				}
				?>
				<input name="podlove_website[use_post_permastruct]" id="use_post_permastruct" type="checkbox" <?php checked( $use_post_permastruct, 'on' ) ?>> <?php _e( 'Use the same permalink structure as posts', 'podlove' ); ?>
				<div id="custom_podcast_permastruct"<?php if ( $use_post_permastruct ) echo ' style="display:none;"' ?>>
					<code><?php echo get_option('home'); ?></code>
					<input name="podlove_website[custom_episode_slug]" id="custom_episode_slug" type="text" value="<?php echo $custom_episode_slug ?>">
					<p><span class="description">
						<?php echo __( '
							Placeholders: %podcast% (post name slug), %post_id%, %year%, %monthnum%, %day%, %hour%, %minute%, %second%, %category%, %author%<br>
							Example schemes: <code>/%podcast%</code>, <code>/episode/%podcast%</code>, <code>/%year%/%monthnum%/%podcast%</code>', 'podlove' );
						?>
					</span></p>
				</div>
				
				<script type="text/javascript">
				jQuery(function($) {
					$(document).ready(function() {

						function handle_permastruct_settings() {
							if ( $("#use_post_permastruct").is( ':checked' ) ) {
								$("#custom_podcast_permastruct").slideUp();
							} else {
								$("#custom_podcast_permastruct").slideDown();
							}
						}

						$("#use_post_permastruct").on("click", function(e) {
							handle_permastruct_settings();
						});

						handle_permastruct_settings();
					});
				});
				</script>

				<style type="text/css">
				#custom_podcast_permastruct {
					margin-top: 10px;
				}
				</style>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_episode_archive',
			/* $title    */ sprintf(
				'<label for="episode_archive">%s</label>',
				__( 'Episode archive', 'podlove' )
			),
			/* $callback */ function () {

				$enable_episode_archive = \Podlove\get_setting( 'website', 'episode_archive' );
				$episode_archive_slug   = \Podlove\get_setting( 'website', 'episode_archive_slug' );

				if ( $blog_prefix = \Podlove\get_blog_prefix() ) {
					$episode_archive_slug = preg_replace( '|^/?blog|', '', $episode_archive_slug );
				}
				?>
				<input name="podlove_website[episode_archive]" id="episode_archive" type="checkbox" <?php checked( $enable_episode_archive, 'on' ) ?>> <?php _e( 'Enable episode archive', 'podlove' ); ?>
				<div id="episode_archive_slug_edit"<?php if ( !$enable_episode_archive ) echo ' style="display:none;"' ?>>
					<code><?php echo get_option('home') . $blog_prefix; ?></code>
					<input name="podlove_website[episode_archive_slug]" id="episode_archive_slug" type="text" value="<?php echo $episode_archive_slug ?>">
				</div>
				
				<script type="text/javascript">
				jQuery(function($) {
					$(document).ready(function() {
						$("#episode_archive").on("click", function(e) {
							if ( $(this).is( ':checked' ) ) {
								$("#episode_archive_slug_edit").slideDown();
							} else {
								$("#episode_archive_slug_edit").slideUp();
							}
						});
					});
				});
				</script>

				<style type="text/css">
				#episode_archive_slug_edit {
					margin-top: 10px;
				}
				</style>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_url_template',
			/* $title    */ sprintf(
				'<label for="url_template">%s</label>',
				__( 'Episode Asset URL Template.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_website[url_template]" id="url_template" type="text" value="<?php echo \Podlove\get_setting( 'website', 'url_template' ) ?>" class="large-text">
				<p>
					<span class="description">
						<?php echo __( 'Is used to generate URLs. You probably don\'t want to change this.', 'podlove' ); ?>
					</span>
				</p>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);

		register_setting( Settings::$pagehook, 'podlove_website', function($options) {
			/**
			 * handle checkboxes
			 */
			$checkboxes = array(
				'merge_episodes',
				'hide_wp_feed_discovery',
				'use_post_permastruct',
				'episode_archive'
			);
			foreach ( $checkboxes as $checkbox_key ) {
				if ( ! isset( $options[ $checkbox_key ] ) )
					$options[ $checkbox_key ] = 'off';
			}

			/**
			 * handle permastructs
			 */
			$prefix = $blog_prefix = '';
			$iis7_permalinks = iis7_supports_permalinks();

			if ( ! got_mod_rewrite() && ! $iis7_permalinks )
				$prefix = '/index.php';

			if ( is_multisite() && ! is_subdomain_install() && is_main_site() )
				$blog_prefix = '';
		
			// Episode permastruct
			if ( array_key_exists( 'custom_episode_slug', $options ) ) {
				$options['custom_episode_slug'] = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', $options['custom_episode_slug'] ) );
				
				if ( $prefix && $blog_prefix ) {
					$options['custom_episode_slug'] = $prefix . preg_replace( '#^/?index\.php#', '', $options['custom_episode_slug'] );
				} else {
					$options['custom_episode_slug'] = $blog_prefix . $options['custom_episode_slug'];
				}
			}
				
			// Archive slug
			if ( array_key_exists( 'episode_archive_slug', $options ) ) {
				$options['episode_archive_slug'] = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', $options['episode_archive_slug'] ) );
			
				if ( $prefix && $blog_prefix ) {
					$options['episode_archive_slug'] = $prefix . preg_replace( '#^/?index\.php#', '', $options['episode_archive_slug'] );
				} else {
					$options['episode_archive_slug'] = $blog_prefix . $options['episode_archive_slug'];
				}
			}
			
			return $options;
		} );
	}
}