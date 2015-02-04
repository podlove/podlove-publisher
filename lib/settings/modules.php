<?php
namespace Podlove\Settings;
use \Podlove\Model;

class Modules {

	use \Podlove\HasPageDocumentationTrait;

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Modules::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Modules',
			/* $menu_title */ 'Modules',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_modules_handle',
			/* $function   */ array( $this, 'page' )
		);

		$this->init_page_documentation(self::$pagehook);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_modules',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { /* section head html */ }, 		
			/* $page	 */ Modules::$pagehook	
		);

		$grouped_modules = array();
		$modules = \Podlove\Modules\Base::get_all_module_names();
		foreach ( $modules as $module_name ) {
			$class = \Podlove\Modules\Base::get_class_by_module_name( $module_name );

			if ( ! class_exists( $class ) )
				continue;

			$module = $class::instance();
			$module_options = $module->get_registered_options();

			if ( $group = $module->get_module_group() ) {
				add_settings_section( 
					'podlove_setting_module_group_' . $group,
					ucwords( $group ),
					function () {},
					Modules::$pagehook );
			}

			if ( $module_options ) {
				register_setting( Modules::$pagehook, $module->get_module_options_name() );
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
					
					do_action( 'podlove_module_before_settings_' . $module_name );

					if ( $module_options ) {

						?><h4><?php echo __( 'Settings' ) ?></h4><?php

						// prepare settings object because form framework expects an object
						$settings_object = new \stdClass();
						foreach ( $module_options as $key => $value ) {
							$settings_object->$key = $module->get_module_option( $key );
						}

						\Podlove\Form\build_for( $settings_object, array( 'context' => $module->get_module_options_name(), 'submit_button' => false, 'form' => false ), function ( $form ) use ( $module_options ) {
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

					do_action( 'podlove_module_after_settings_' . $module_name );
				},
				/* $page     */ Modules::$pagehook,  
				/* $section  */ $group ? 'podlove_setting_module_group_' . $group : 'podlove_settings_modules'
			);
		}

		register_setting( Modules::$pagehook, 'podlove_active_modules' );
	}
	
	function page() {
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Podlove Publisher Modules' ) ?></h2>

			<form method="post" action="options.php">
				<?php settings_fields( Modules::$pagehook ); ?>
				<?php do_settings_sections( Modules::$pagehook ); ?>
				
				<?php submit_button( __( 'Save Changes' ), 'button-primary', 'submit', TRUE ); ?>
			</form>
		</div>	
		<?php
	}
	
}