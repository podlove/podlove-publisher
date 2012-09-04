<?php
namespace Podlove\Settings;

class Settings {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Settings::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Settings',
			/* $menu_title */ 'Settings',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_settings_handle',
			/* $function   */ array( $this, 'page' )
		);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_general',
			/* $title 	 */ __( 'General Settings', 'podlove' ),	
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
		
		add_settings_section(
			/* $id 		 */ 'podlove_settings_modules',
			/* $title 	 */ __( 'Modules', 'podlove' ),	
			/* $callback */ function () { /* section head html */ }, 		
			/* $page	 */ Settings::$pagehook	
		);

		$modules = \Podlove\Modules\Base::get_all_module_names();
		foreach ( $modules as $module_name ) {
			$class = \Podlove\Modules\Base::get_class_by_module_name( $module_name );

			if ( ! class_exists( $class ) )
				continue;

			$module = $class::instance();
			$module_options = $module->get_registered_options();

			if ( $module_options ) {
				register_setting( Settings::$pagehook, $module->get_module_options_name() );
			}

			add_settings_field(
				/* $id       */ 'podlove_setting_module_' . $module_name,
				/* $title    */ sprintf(
					'<label for="' . $module_name . '">%s</label>',
					$module->get_module_name()
				),
				/* $callback */ function () use ( $module, $module_name, $module_options ) {
					?>
					<label for="<?php echo $module_name ?>">
						<input name="podlove_active_modules[<?php echo $module_name ?>]" id="<?php echo $module_name ?>" type="checkbox" <?php checked( \Podlove\Modules\Base::is_active( $module_name ), true ) ?>>
						<?php echo $module->get_module_description() ?>
					</label>
					<?php
					

					if ( $module_options ) {

						?><h4><?php echo __( 'Settings' ) ?></h4><?php

						// prepare settings object because form framework expects an object
						$settings_object = new \stdClass();
						foreach ( $module_options as $key => $value ) {
							$settings_object->$key = $module->get_module_option( $key );
						}

						\Podlove\Form\build_for( $settings_object, array( 'context' => $module->get_module_options_name(), 'submit_button' => false ), function ( $form ) use ( $module_options ) {
							$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

							foreach ( $module_options as $module_option_name => $args ) {
								call_user_func_array(
									array( $wrapper, $args['input_type'] ),
									array(
										$module_option_name,
										$args['args']
									)
								);
							}

						} );
					}
				},
				/* $page     */ Settings::$pagehook,  
				/* $section  */ 'podlove_settings_modules'
			);

		}

		register_setting( Settings::$pagehook, 'podlove' );
		register_setting( Settings::$pagehook, 'podlove_active_modules' );
	}
	
	function page() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php echo __( 'Settings' ) ?></h2>

			<form method="post" action="options.php">
				<?php settings_fields( Settings::$pagehook ); ?>
				<?php do_settings_sections( Settings::$pagehook ); ?>
				
				<?php submit_button( __( 'Save Changes' ), 'button-primary', 'submit', TRUE ); ?>
			</form>
		</div>	
		<?php
	}
	
}