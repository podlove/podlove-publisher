<?php 
/**
 * Version management for database migrations.
 * 
 * Database changes require special care:
 * - the model has to be adjusted for users installing the plugin
 * - the current setup has to be migrated for current users
 * 
 * These migrations are a way to handle current users. They do *not*
 * run on plugin activation.
 * 
 * Pattern:
 * 
 * - increment \Podlove\DATABASE_VERSION constant by 1, e.g.
 * 		```php
 * 		define( __NAMESPACE__ . '\DATABASE_VERSION', 2 );
 * 		```
 * 		
 * - add a case in `\Podlove\run_migrations_for_version`, e.g.
 * 		```php
 * 		function run_migrations_for_version( $version ) {
 *			global $wpdb;
 *			switch ( $version ) {
 *				case 2:
 *					$wbdb-> // run sql or whatever
 *					break;
 *			}
 *		}
 *		```
 *		
 *		Feel free to move the migration code into a separate function if it's
 *		rather complex.
 *		
 * - adjust the main model / setup process so new users installing the plugin
 *   will have these changes too
 *   
 * - Test the migrations! :)
 */

namespace Podlove;

define( __NAMESPACE__ . '\DATABASE_VERSION', 1 );

add_action( 'init', function () {
	
	$database_version = get_option( 'podlove_database_version' );

	if ( $database_version === false ) {
		// plugin has just been installed
		update_option( 'podlove_database_version', DATABASE_VERSION );
	} elseif ( $database_version < DATABASE_VERSION ) {
		// run one or multiple migrations
		for ( $i = $database_version+1; $i <= DATABASE_VERSION; $i++ ) { 
			\Podlove\run_migrations_for_version( $i );
		}
		update_option( 'podlove_database_version', DATABASE_VERSION );
	}

} );

function run_migrations_for_version( $version ) {
	// global $wpdb;
	// 
	// switch ( $version ) {
	// 	case 2:
	// 		break;
	// }

}
