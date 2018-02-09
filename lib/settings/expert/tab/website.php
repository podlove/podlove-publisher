<?php
namespace Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class Website extends Tab {

	public function get_slug() {
		return 'website';
	}

	public function init() {
		
		// always flush rewrite rules here for custom_episode_slug setting
		if ($this->is_active()) {
			set_transient( 'podlove_needs_to_flush_rewrite_rules', true );
		}

		add_settings_section(
			/* $id 		 */ 'podlove_settings_general',
			/* $title 	 */ __( '', 'podlove-podcasting-plugin-for-wordpress' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Website Settings', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_files',
			/* $title 	 */ __( '', 'podlove-podcasting-plugin-for-wordpress' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Files & Downloads', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_feeds',
			/* $title 	 */ __( '', 'podlove-podcasting-plugin-for-wordpress' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Feeds', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_merge_episodes',
			/* $title    */ sprintf(
				'<label for="merge_episodes">%s</label>',
				__( 'Combine blog & podcast', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_website[merge_episodes]" id="merge_episodes" type="checkbox" <?php checked( \Podlove\get_setting( 'website', 'merge_episodes' ), 'on' ) ?>>
				<?php
				echo __( 'Include episode posts on the front page and in the blog feed', 'podlove-podcasting-plugin-for-wordpress' );
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_hide_wp_feed_discovery',
			/* $title    */ sprintf(
				'<label for="hide_wp_feed_discovery">%s</label>',
				__( 'Hide blog feeds', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_website[hide_wp_feed_discovery]" id="hide_wp_feed_discovery" type="checkbox" <?php checked( \Podlove\get_setting( 'website', 'hide_wp_feed_discovery' ), 'on' ) ?>>
				<?php
				echo __( 'Hide default WordPress feeds for blog and comments (no auto-discovery).', 'podlove-podcasting-plugin-for-wordpress' );
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_custom_episode_slug',
			/* $title    */ sprintf(
				'<label for="custom_episode_slug">%s</label>',
				__( 'Permalink structure for episodes', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {

				$use_post_permastruct = \Podlove\get_setting( 'website', 'use_post_permastruct' );
				$custom_episode_slug  = \Podlove\get_setting( 'website', 'custom_episode_slug' );

				if ( $blog_prefix = \Podlove\get_blog_prefix() ) {
					$custom_episode_slug = preg_replace( '|^/?blog|', '', $custom_episode_slug );
				}
				
				\Podlove\load_template('expert_settings/website/custom_episode_slug', compact('use_post_permastruct', 'custom_episode_slug'));
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_episode_archive',
			/* $title    */ sprintf(
				'<label for="episode_archive">%s</label>',
				__( 'Episode pages', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {

				$enable_episode_archive = \Podlove\get_setting( 'website', 'episode_archive' );
				$episode_archive_slug   = \Podlove\get_setting( 'website', 'episode_archive_slug' );

				if ( $blog_prefix = \Podlove\get_blog_prefix() ) {
					$episode_archive_slug = preg_replace( '|^/?blog|', '', $episode_archive_slug );
				}

				\Podlove\load_template('expert_settings/website/episode_archive', compact('enable_episode_archive', 'episode_archive_slug', 'blog_prefix'));
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_landing_page',
			/* $title    */ sprintf(
				'<label for="landing_page">%s</label>',
				__( 'Podcast landing page', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {

				$landing_page = \Podlove\get_setting( 'website', 'landing_page' );

				$landing_page_options = array(
					array( 'value' => 'homepage', 'text' => __('Front page', 'podlove-podcasting-plugin-for-wordpress') ),
					array( 'value' => 'archive',  'text' => __('Episode pages', 'podlove-podcasting-plugin-for-wordpress') ),
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

				\Podlove\load_template('expert_settings/website/landing_page', compact('landing_page', 'landing_page_options'));
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_blog_post_title',
			/* $title    */ sprintf(
				'<label for="enable_generated_blog_post_title">%s</label>',
				__( 'Blog Episode Titles', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {

				$enable_generated_blog_post_title = \Podlove\get_setting( 'website', 'enable_generated_blog_post_title' );
				$blog_title_template = \Podlove\get_setting( 'website', 'blog_title_template' );

				\Podlove\load_template('expert_settings/website/blog_post_title', compact('enable_generated_blog_post_title', 'blog_title_template'));
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_episode_number_padding',
			/* $title    */ sprintf(
				'<label for="episode_number_padding">%s</label>',
				__( 'Episode Number Padding', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				$episode_number_padding = \Podlove\get_setting( 'website', 'episode_number_padding' );
				?>
				<input type="number" name="podlove_website[episode_number_padding]" value="<?php echo $episode_number_padding ?>" id="episode_number_padding" class="large-text" style="max-width: 66px" />
				<p>
					<span class="description">
						<?php echo __('Preferred episode number length. If an episode number is smaller than desired, it will be prefixed with zeroes. For example, episode number 1 with a padding of 3 will be printed as 001.', 'podlove-podcasting-plugin-for-wordpress') ?>
					</span>
				</p>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_url_template',
			/* $title    */ sprintf(
				'<label for="url_template">%s</label>',
				__( 'Episode Asset URL Template.', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_website[url_template]" id="url_template" type="text" value="<?php echo \Podlove\get_setting( 'website', 'url_template' ) ?>" class="large-text podlove-check-input">
				<p>
					<span class="description">
						<?php echo __( 'Is used to generate URLs. You probably don\'t want to change this.', 'podlove-podcasting-plugin-for-wordpress' ); ?>
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
				__( 'Check for Assets with SSL-peer-verification.', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_website[ssl_verify_peer]" id="ssl_verify_peer" type="checkbox" <?php checked( \Podlove\get_setting( 'website', 'ssl_verify_peer' ), 'on' ) ?>>
				<?php echo __('If you provide your assets via https with a self-signed or not verifiable SSL-certificate, podlove should display your assets as non exiting. You might solve this by deactivating the ssl peer verification for asset checking. (Detailed: This sets "CURLOPT_SSL_VERIFYPEER" to FALSE.)', 'podlove-podcasting-plugin-for-wordpress') ?>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_files'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_feeds_skip_redirect',
			/* $title    */ sprintf(
				'<label for="feeds_skip_redirect">%s</label>',
				__( 'Allow to skip feed redirects', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_website[feeds_skip_redirect]" id="feeds_skip_redirect" type="checkbox" <?php checked( \Podlove\get_setting( 'website', 'feeds_skip_redirect' ), 'on' ) ?>>
				<?php echo __('If you need to debug you feeds while using a feed proxy, add <code>?redirect=no</code> to the feed URL to skip the redirect.', 'podlove-podcasting-plugin-for-wordpress') ?>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_feeds'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_feeds_force_protocol',
			/* $title    */ sprintf(
				'<label for="feeds_force_protocol">%s</label>',
				__( 'Website Protocol', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ [$this, 'feeds_force_protocol'],
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_feeds'
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
				'ssl_verify_peer',
				'feeds_skip_redirect'
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

	public function feeds_force_protocol() {
		$this->feeds_force_protocol_setting();
		$this->feeds_force_protocol_issues();
	}

	private function feeds_force_protocol_setting()
	{
		$options = [
			'default'    => __('Hands Off (leave everything as configured by WordPress)', 'podlove-podcasting-plugin-for-wordpress'),
			'http'       => __('1 - Website is delivered via http (including podcast feeds)', 'podlove-podcasting-plugin-for-wordpress'),
			'https'      => __('2 - Website is delivered via https (including podcast feeds)', 'podlove-podcasting-plugin-for-wordpress'),
			'http_feeds' => __('3 - Website is delivered via https (excluding podcast feeds which will be delivered via http)', 'podlove-podcasting-plugin-for-wordpress')
		];

		\Podlove\load_template('expert_settings/website/feeds_force_protocol', compact('options'));		
	}

	private function feeds_force_protocol_issues()
	{
		$force = \Podlove\get_setting('website', 'feeds_force_protocol');

		$home_url = parse_url(\get_option('home'));
		$site_url = parse_url(\get_option('siteurl'));

		$issues = [];

		if ($force == 'http') {
			// todo: explain where home/site url are set
			if ($home_url['scheme'] !== 'http') {
				$issues[] = sprintf(__('You claim your website is all http but your WordPress Address (home url) scheme is %s', 'podlove-podcasting-plugin-for-wordpress'), $home_url['scheme']);
			}
			if ($site_url['scheme'] !== 'http') {
				$issues[] = sprintf(__('You claim your website is all http but your Site Address (site url) scheme is %s', 'podlove-podcasting-plugin-for-wordpress'), $home_url['scheme']);
			}
		} elseif ($force == 'https' || $force == 'http_feeds') {
			// todo: explain where home/site url are set
			if ($home_url['scheme'] !== 'https') {
				$issues[] = sprintf(__('You claim your website is all https but your WordPress Address (home url) scheme is %s', 'podlove-podcasting-plugin-for-wordpress'), $home_url['scheme']);
			}
			if ($site_url['scheme'] !== 'https') {
				$issues[] = sprintf(__('You claim your website is all https but your Site Address (site url) scheme is %s', 'podlove-podcasting-plugin-for-wordpress'), $home_url['scheme']);
			}					
		}

		\Podlove\load_template('expert_settings/website/feeds_force_protocol_issues', compact('issues'));
	}

}
