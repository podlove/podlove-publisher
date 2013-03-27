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
			/* $callback */ function () { echo '<h3>' . __( 'General', 'podlove' ) . '</h3>'; },
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
				__( 'URL scheme for episodes', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove[custom_episode_slug]" id="custom_episode_slug" type="text" value="<?php echo \Podlove\get_setting( 'custom_episode_slug' ) ?>">
				<p>
					<span class="description"><?php echo __( 'Placeholders: %postname%, %post_id%, %year%, %monthnum%, %day%, %hour%, %minute%, %second%, %category%, %author%', 'podlove' ); ?></span>
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
				__( 'Episode Asset URL Template.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove[url_template]" id="url_template" type="text" value="<?php echo \Podlove\get_setting( 'url_template' ) ?>" class="large-text">
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

		add_settings_section(
			/* $id 		 */ 'podlove_settings_episode',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Episodes', 'podlove' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_episode_record_date',
			/* $title    */ sprintf(
				'<label for="enable_episode_record_date">%s</label>',
				__( 'Enable record date field.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove[enable_episode_record_date]" id="enable_episode_record_date" type="radio" value="1" <?php checked( \Podlove\get_setting( 'enable_episode_record_date' ), 1 ) ?> /> <?php echo __( 'enable', 'podlove' ) ?>
				</label>
				<label>
					<input name="podlove[enable_episode_record_date]" id="enable_episode_record_date" type="radio" value="0" <?php checked( \Podlove\get_setting( 'enable_episode_record_date' ), 0 ) ?> /> <?php echo __( 'disable', 'podlove' ) ?>
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
					<input name="podlove[enable_episode_publication_date]" id="enable_episode_publication_date" type="radio" value="1" <?php checked( \Podlove\get_setting( 'enable_episode_publication_date' ), 1 ) ?> /> <?php echo __( 'enable', 'podlove' ) ?>
				</label>
				<label>
					<input name="podlove[enable_episode_publication_date]" id="enable_episode_publication_date" type="radio" value="0" <?php checked( \Podlove\get_setting( 'enable_episode_publication_date' ), 0 ) ?> /> <?php echo __( 'disable', 'podlove' ) ?>
				</label>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
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