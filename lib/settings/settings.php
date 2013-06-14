<?php
namespace Podlove\Settings;

class Tab {

	/**
	 * Tab title
	 * @var string
	 */
	private $title;

	/**
	 * Tab slug used in URLs
	 * @var string
	 */
	private $slug;

	/**
	 * If this is true, use it if no tab is selected.
	 * @var bool
	 */
	private $is_default;

	public function __construct( $title, $is_default = false ) {
		$this->set_title( $title );
		$this->is_default = $is_default;
	}

	public function is_active() {
		$is_current_tab = isset( $_REQUEST['podlove_tab'] ) && $this->get_slug() == $_REQUEST['podlove_tab'];
		return $is_current_tab || ! isset( $_REQUEST['podlove_tab'] ) && $this->is_default;
	}

	public function get_title() {
		return $this->title;
	}

	public function set_title( $title ) {
		$this->title = $title;
		$this->slug = strtolower( \Podlove\slugify( $title ) );
	}

	public function get_slug() {
		return $this->slug;
	}

	public function get_url() {
		return sprintf( "?page=%s&podlove_tab=%s", $_REQUEST['page'], $this->get_slug() );
	}

	public function page() {
		?>
		<form method="post" action="options.php">
			<?php if ( isset( $_REQUEST['podlove_tab'] ) ): ?>
				<input type="hidden" name="podlove_tab" value="<?php echo $_REQUEST['podlove_tab'] ?>" />
			<?php endif; ?>
			<?php settings_fields( Settings::$pagehook ); ?>
			<?php do_settings_sections( Settings::$pagehook ); ?>
			
			<?php submit_button( __( 'Save Changes' ), 'button-primary', 'submit', TRUE ); ?>
		</form>
		<?php
	}

	public function init() {
		throw Exception( "You need to subclass Tab and implement Tab::init" );
	}
}

class Tabs {

	/**
	 * Tab Bar Title
	 * @var string
	 */
	private $title = '';

	/**
	 * List of tab objects
	 * @var array
	 */
	private $tabs = array();

	public function __construct( $title ) {
		$this->title = $title;
	}

	public function addTab( Tab $tab ) {
		$this->tabs[] = $tab;
	}

	public function getTabsHTML() {

		$html  = '<h2 class="nav-tab-wrapper">';
		$html .= $this->title . "\n";
		foreach ( $this->tabs as $tab ) {
			$html .= sprintf(
				'<a href="%s" class="nav-tab%s">%s</a>',
				$tab->get_url(),
				$tab->is_active() ? ' nav-tab-active' : '',
				$tab->get_title()
			);
		}
		$html .= '</h2>';

		return $html;
	}

	public function getCurrentTabPage() {
		return $this->getCurrentTab()->page();
	}

	public function initCurrentTab() {
		return $this->getCurrentTab()->init();
	}

	public function initAllTabs() {
		foreach ( $this->tabs as $tab ) {
			$tab->init();
		}
	}

	private function getCurrentTab() {
		foreach ( $this->tabs as $tab )
			if ( $tab->is_active() )
				return $tab;
	}

}

class WebsiteTab extends Tab {
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

class MetadataTab extends Tab {
	public function init() {

		add_settings_section(
			/* $id 		 */ 'podlove_settings_episode',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Episode Metadata Settings', 'podlove' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_episode_record_date',
			/* $title    */ sprintf(
				'<label for="enable_episode_record_date">%s</label>',
				__( 'Enable recording date field.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove_metadata[enable_episode_record_date]" id="enable_episode_record_date" type="radio" value="1" <?php checked( \Podlove\get_setting( 'metadata', 'enable_episode_record_date' ), 1 ) ?> /> <?php echo __( 'enable', 'podlove' ) ?>
				</label>
				<label>
					<input name="podlove_metadata[enable_episode_record_date]" id="enable_episode_record_date" type="radio" value="0" <?php checked( \Podlove\get_setting( 'metadata', 'enable_episode_record_date' ), 0 ) ?> /> <?php echo __( 'disable', 'podlove' ) ?>
				</label>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_episode_publication_date',
			/* $title    */ sprintf(
				'<label for="enable_episode_publication_date">%s</label>',
				__( 'Enable publication date field.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove_metadata[enable_episode_publication_date]" id="enable_episode_publication_date" type="radio" value="1" <?php checked( \Podlove\get_setting( 'metadata', 'enable_episode_publication_date' ), 1 ) ?> /> <?php echo __( 'enable', 'podlove' ) ?>
				</label>
				<label>
					<input name="podlove_metadata[enable_episode_publication_date]" id="enable_episode_publication_date" type="radio" value="0" <?php checked( \Podlove\get_setting( 'metadata', 'enable_episode_publication_date' ), 0 ) ?> /> <?php echo __( 'disable', 'podlove' ) ?>
				</label>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		register_setting( Settings::$pagehook, 'podlove_metadata' );
	}
}

class RedirectsTab extends Tab {
	public function init() {
		add_settings_section(
			/* $id 		 */ 'podlove_settings_redirects',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Redirects', 'podlove' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_redirect',
			/* $title    */ sprintf(
				'<label for="podlove_setting_redirect">%s</label>',
				__( 'Permanent URL Redirects', 'podlove' )
			),
			/* $callback */ function () {
				$redirect_settings = \Podlove\get_setting( 'redirects', 'podlove_setting_redirect' );

				if ( ! is_array( $redirect_settings ) )
					$redirect_settings = array();

				?>
				<table class="wp-list-table widefat podlove_redirects">
					<thead>
						<tr>
							<th><?php echo __( 'From URL', 'podlove' ) ?></th>
							<th><?php echo __( 'To URL', 'podlove' ) ?></th>
							<th class="delete"></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$index = 0;
						foreach ( $redirect_settings as $index => $redirect_setting ) {
							if ( $redirect_setting['from'] || $redirect_setting['to'] ) {
								?>
								<tr data-index="<?php echo $index ?>">
									<td>
										<input type="text" name="podlove_redirects[podlove_setting_redirect][<?php echo $index ?>][from]" value="<?php echo $redirect_setting['from'] ?>">
									</td>
									<td>
										<input type="text" name="podlove_redirects[podlove_setting_redirect][<?php echo $index ?>][to]" value="<?php echo $redirect_setting['to'] ?>">
									</td>
									<td class="delete">
										<a href="#" class="button delete"><?php echo __( 'delete', 'podlove' ) ?></a>
									</td>
								</tr>
								<?php
							}
						}
						?>
						<tr data-index="<?php echo $index + 1 ?>">
							<td>
								<input type="text" name="podlove_redirects[podlove_setting_redirect][<?php echo $index + 1 ?>][from]">
							</td>
							<td>
								<input type="text" name="podlove_redirects[podlove_setting_redirect][<?php echo $index + 1 ?>][to]">
							</td>
							<td class="delete">
								<a href="#" class="button delete"><?php echo __( 'delete', 'podlove' ) ?></a>
							</td>
						</tr>
					</tbody>
				</table>

				<p>
					<a href="#" id="podlove_add_new_rule" class="button"><?php echo __( 'Add new rule' ); ?></a>
				</p>
				<p class="description">
					<?php echo __( 'Create custom permanent redirects. URLs can be absolute like <code>http://example.com/feed</code> or relative to the website like <code>/feed</code>.', 'podlove' ) ?>
				</p>

				<script type="text/javascript">
				jQuery(function($) {
					$(document).ready(function() {

						$(".podlove_redirects").on("click", "td.delete a", function(e) {
							e.preventDefault();
							$(this).closest("tr").remove();
							return false;
						});

						$("#podlove_add_new_rule").on("click", function(e) {
							e.preventDefault();

							var index = $(".podlove_redirects tr:last").data("index") + 1,
							    html = '';

							html += "<tr data-index=\"" + index + "\">";
							html += "<td><input type=\"text\" name=\"podlove_redirects[podlove_setting_redirect][" + index + "][from]\"></td>";
							html += "<td><input type=\"text\" name=\"podlove_redirects[podlove_setting_redirect][" + index + "][to]\"></td>";
							html += "<td class=\"delete\"><a href=\"#\" class=\"button\"><?php echo __( 'delete', 'podlove' ) ?></a></td>";
							html += "</tr>";


							$(".podlove_redirects tbody").append(html);

							return false;
						});
					});
				});
				</script>

				<style type="text/css">
				.podlove_redirects th.delete, .podlove_redirects td.delete {
					width: 60px;
					text-align: right;
				}
				.podlove_redirects td input {
					width: 100%;
				}
				</style>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_redirects'
		);

		register_setting( Settings::$pagehook, 'podlove_redirects' );
	}
}

/**
 * Expert Settings
 */
class Settings {

	static $pagehook;
	private $tabs;
	
	public function __construct( $handle ) {
		
		Settings::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Expert Settings',
			/* $menu_title */ 'Expert Settings',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_settings_handle',
			/* $function   */ array( $this, 'page' )
		);

		$tabs = new Tabs( __( 'Expert Settings', 'podlove' ) );
		$tabs->addTab( new WebsiteTab( __( 'Website', 'podlove' ), true ) );
		$tabs->addTab( new MetadataTab( __( 'Metadata', 'podlove' ) ) );
		$tabs->addTab( new RedirectsTab( __( 'Redirects', 'podlove' ) ) );
		// $tabs->addTab( new Tab( __( 'File Types', 'podlove' ) ) );
		$this->tabs = $tabs;
		$this->tabs->initCurrentTab();
	}
	
	function page() {
		// hack: always flush rewrite rules here for custom_episode_slug setting
		flush_rewrite_rules();
		?>
		<div class="wrap">
			<?php
			screen_icon( 'podlove-podcast' );
			echo $this->tabs->getTabsHTML();
			echo $this->tabs->getCurrentTabPage();
			?>
		</div>	
		<?php
	}
	
}