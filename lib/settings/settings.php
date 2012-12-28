<?php
namespace Podlove\Settings;

class Settings {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Settings::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Expert Settings',
			/* $menu_title */ 'Expert Settings',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_settings_handle',
			/* $function   */ array( $this, 'page' )
		);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_general',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { /* section head html */ }, 		
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
				<input name="podlove[merge_episodes]" id="merge_episodes" type="checkbox" <?php checked( \Podlove\get_setting( 'merge_episodes' ), 'on' ) ?>>
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
				<input name="podlove[hide_wp_feed_discovery]" id="hide_wp_feed_discovery" type="checkbox" <?php checked( \Podlove\get_setting( 'hide_wp_feed_discovery' ), 'on' ) ?>>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_custom_episode_slug',
			/* $title    */ sprintf(
				'<label for="custom_episode_slug">%s</label>',
				__( 'URL segment prefix for podcast episode posts. Leave empty to remove the prefix.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove[custom_episode_slug]" id="custom_episode_slug" type="text" value="<?php echo \Podlove\get_setting( 'custom_episode_slug' ) ?>">
				<p>
					<span class="description"><?php echo __( 'Must be a suitable URL part: lowercase characters, numbers and hyphens.', 'podlove' ); ?></span>
				</p>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		register_setting( Settings::$pagehook, 'podlove' );
		
	}
	
	function page() {
		// hack: always flush rewrite rules here for custom_episode_slug setting
		flush_rewrite_rules();
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Expert Settings' ) ?></h2>

			<form method="post" action="options.php">
				<?php settings_fields( Settings::$pagehook ); ?>
				<?php do_settings_sections( Settings::$pagehook ); ?>
				
				<?php submit_button( __( 'Save Changes' ), 'button-primary', 'submit', TRUE ); ?>
			</form>
		</div>	
		<?php
	}
	
}