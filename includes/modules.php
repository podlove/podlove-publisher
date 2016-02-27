<?php 
/**
 * Register Publisher Modules
 */

use \Podlove\Modules;
use \Podlove\Log;

// init modules
add_action( 'plugins_loaded', function () {
	$modules = Modules\Base::get_active_module_names();

	if ( empty( $modules ) )
		return;

	foreach ( $modules as $module_name ) {
		$class = Modules\Base::get_class_by_module_name( $module_name );
		if ( class_exists( $class ) ) {
			$class::instance()->load();
		} else {
			Modules\Base::deactivate( $module_name );
			add_action( 'admin_notices', function () use ( $module_name ) {
				?>
				<div id="message" class="error">
					<p>
						<strong><?php echo __( 'Warning' ) ?></strong>
						<?php echo sprintf( __( 'Podlove Module "%s" could not be found and has been deactivated.', 'podlove-podcasting-plugin-for-wordpress' ), $module_name ); ?>
					</p>
				</div>
				<?php
			} );
		}
	}
} );

// Add core modules to "activated modules" to ensure:
// 1. they are active
// 2. activation hook gets fired
add_filter('pre_update_option_podlove_active_modules', function($new_val, $old_val) {

	// bring in form
	$core_modules = [];
	foreach (Modules\Base::get_core_module_names() as $module) {
		$core_modules[$module] = "on";
	}

	return array_merge($new_val, $core_modules);
}, 10, 2);

// fire activation and deactivation hooks for modules
add_action( 'update_option_podlove_active_modules', function( $old_val, $new_val ) {
	$deactivated_modules = array_keys( array_diff_assoc( $old_val, $new_val ) );
	$activated_modules   = array_keys( array_diff_assoc( $new_val, $old_val ) );

	if ( $deactivated_modules ) {
		foreach ($deactivated_modules as $deactivated_module) {
			Log::get()->addInfo( 'Deactivate module "' . $deactivated_module . '"' );
			do_action( 'podlove_module_was_deactivated', $deactivated_module );
			do_action( 'podlove_module_was_deactivated_' . $deactivated_module );
		}
	} 

	if ( $activated_modules ) {
		foreach ($activated_modules as $activated_module) {
			Log::get()->addInfo( 'Activate module "' . $activated_module . '"' );

			// init module before firing hooks
			$class = Modules\Base::get_class_by_module_name( $activated_module );
			if ( class_exists( $class ) )
				$class::instance()->load();

			do_action( 'podlove_module_was_activated', $activated_module );
			do_action( 'podlove_module_was_activated_' . $activated_module );
		}
	}
}, 10, 2 );