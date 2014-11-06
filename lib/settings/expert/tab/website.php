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

		add_settings_section(
			/* $id 		 */ 'podlove_settings_files',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Files & Downloads', 'podlove' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_merge_episodes',
			/* $title    */ sprintf(
				'<label for="merge_episodes">%s</label>',
				__( 'Combine blog & podcast', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_website[merge_episodes]" id="merge_episodes" type="checkbox" <?php checked( \Podlove\get_setting( 'website', 'merge_episodes' ), 'on' ) ?>>
				<?php
				echo __( 'Include episode posts on the front page and in the blog feed', 'podlove' );
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_hide_wp_feed_discovery',
			/* $title    */ sprintf(
				'<label for="hide_wp_feed_discovery">%s</label>',
				__( 'Hide blog feeds', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_website[hide_wp_feed_discovery]" id="hide_wp_feed_discovery" type="checkbox" <?php checked( \Podlove\get_setting( 'website', 'hide_wp_feed_discovery' ), 'on' ) ?>>
				<?php
				echo __( 'Hide default WordPress feeds for blog and comments (no auto-discovery).', 'podlove' );
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
				__( 'Episode pages', 'podlove' )
			),
			/* $callback */ function () {

				$enable_episode_archive = \Podlove\get_setting( 'website', 'episode_archive' );
				$episode_archive_slug   = \Podlove\get_setting( 'website', 'episode_archive_slug' );

				if ( $blog_prefix = \Podlove\get_blog_prefix() ) {
					$episode_archive_slug = preg_replace( '|^/?blog|', '', $episode_archive_slug );
				}
				?>
				<input name="podlove_website[episode_archive]" id="episode_archive" type="checkbox" <?php checked( $enable_episode_archive, 'on' ) ?>> <?php _e( 'Enable episode pages: a complete, paginated list of episodes, sorted by publishing date.', 'podlove' ); ?>
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
			/* $id       */ 'podlove_setting_landing_page',
			/* $title    */ sprintf(
				'<label for="landing_page">%s</label>',
				__( 'Podcast landing page', 'podlove' )
			),
			/* $callback */ function () {

				$landing_page = \Podlove\get_setting( 'website', 'landing_page' );

				$landing_page_options = array(
					array( 'value' => 'homepage', 'text' => __('Front page', 'podlove') ),
					array( 'value' => 'archive',  'text' => __('Episode pages', 'podlove') ),
					array( 'text' => '––––––––––', 'disabled' => true ),
				);

				$pages_query = new \WP_Query( array(
					'post_type' => 'page',
					'nopaging'  => true
				) );

				if ( $pages_query->have_posts() ) {
					while ( $pages_query->have_posts() ) {
						$pages_query->the_post();
						$landing_page_options[] = array('value' => get_the_ID(), 'text' => get_the_title());
					}
				}

				wp_reset_postdata();

				?>
				<select name="podlove_website[landing_page]" id="landing_page">
					<?php foreach ( $landing_page_options as $option ): ?>
						<option
							<?php if ( isset($option['value']) ): ?>
								value="<?php echo $option['value'] ?>"
								<?php if ( $landing_page == $option['value'] ): ?> selected<?php endif; ?>
							<?php endif; ?>
							<?php if ( isset($option['disabled']) && $option['disabled'] ): ?> disabled<?php endif; ?>
						>
							<?php echo $option['text'] ?>
						</option>
					<?php endforeach; ?>
				</select>

				<script type="text/javascript">
				jQuery(function($) {
					$(document).ready(function() {
						var maybe_toggle_episode_archive_option = function() {
							var $archive = $("#episode_archive"),
								$archive_option = $("#landing_page option:eq(1)"),
								$home_option = $("#landing_page option:eq(0)");

							if ($archive.is(':checked')) {
								$archive_option.attr('disabled', false);
							} else {
								$archive_option.attr('disabled', 'disabled');
								// if it was selected before, unselect it
								if ($archive_option.attr('selected') == 'selected') {
									$archive_option.attr('selected', false);
									$home_option.attr('selected', 'selected');
								}
							}

						};

						$("#episode_archive").on("click", function(e) {
							maybe_toggle_episode_archive_option();
						});

						maybe_toggle_episode_archive_option();
					});
				});
				</script>
				<?php echo __('This defines the landing page to your podcast. It is the site that the your podcast feeds link to.', 'podlove') ?>
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
			/* $section  */ 'podlove_settings_files'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_ssl_verify_peer',
			/* $title    */ sprintf(
				'<label for="ssl_verify_peer">%s</label>',
				__( 'Check for Assets with SSL-peer-verification.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_website[ssl_verify_peer]" id="ssl_verify_peer" type="checkbox" <?php checked( \Podlove\get_setting( 'website', 'ssl_verify_peer' ), 'on' ) ?>>
				<?php echo __('If you provide your assets via https with a self-signed or not verifiable SSL-certificate, podlove should display your assets as non exiting. You might solve this by deactivating the ssl peer verification for asset checking. (Detailed: This sets "CURLOPT_SSL_VERIFYPEER" to FALSE.)', 'podlove') ?>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_files'
		);

		register_setting( Settings::$pagehook, 'podlove_website', function($options) {
			/**
			 * handle checkboxes
			 */
			$checkboxes = array(
				'merge_episodes',
				'hide_wp_feed_discovery',
				'use_post_permastruct',
				'episode_archive',
				'ssl_verify_peer'
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