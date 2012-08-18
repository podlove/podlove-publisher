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

define( __NAMESPACE__ . '\DATABASE_VERSION', 9 );

add_action( 'init', function () {
	
	$database_version = get_option( 'podlove_database_version' );

	if ( $database_version === false ) {
		// plugin has just been installed
		update_option( 'podlove_database_version', DATABASE_VERSION );
	} elseif ( $database_version < DATABASE_VERSION ) {
		// run one or multiple migrations
		for ( $i = $database_version+1; $i <= DATABASE_VERSION; $i++ ) { 
			\Podlove\run_migrations_for_version( $i );
			update_option( 'podlove_database_version', $i );
		}
	}

} );

/**
 * Find and run migration for given version number.
 *
 * @todo  move migrations into separate files
 * 
 * @param  int $version
 */
function run_migrations_for_version( $version ) {
	global $wpdb;
	
	switch ( $version ) {
		case 2:
			$sql = sprintf(
				'ALTER TABLE `%s` ADD COLUMN `chapters` TEXT AFTER `cover_art`',
				\Podlove\Model\Release::table_name()
			);
			$wpdb->query( $sql );
			break;
		case 3:
			$sql = sprintf(
				'ALTER TABLE `%s` ADD COLUMN `format` VARCHAR(255) AFTER `slug`',
				\Podlove\Model\Feed::table_name()
			);
			$wpdb->query( $sql );
			break;
		case 4:
			$sql = sprintf(
				'ALTER TABLE `%s` ADD COLUMN `title` VARCHAR(255) AFTER `id`',
				\Podlove\Model\MediaLocation::table_name()
			);
			$wpdb->query( $sql );
			break;
		case 5:
			\Podlove\Modules\Base::activate( 'podlove_web_player' );
			break;
		case 6:
			// title column is "int" for some people. this migration fixes that
			$sql = sprintf(
				'SHOW COLUMNS FROM `wp_podlove_medialocation` WHERE Field = "title"',
				\Podlove\Model\MediaLocation::table_name()
			);
			$row = $wpdb->get_row( $sql );
			if ( strtolower(substr($row->Type, 0, 3)) === 'int' ) {
				$wpdb->query( sprintf(
					'UPDATE `%s` SET title = NULL',
					\Podlove\Model\MediaLocation::table_name()
				) );
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` MODIFY COLUMN `title` VARCHAR(255)',
					\Podlove\Model\MediaLocation::table_name()
				) );
			}
			break;
		case 7:
			// move language from feed to show
			$sql = sprintf(
				'ALTER TABLE `%s` ADD COLUMN `language` VARCHAR(255) AFTER `summary`',
				\Podlove\Model\Show::table_name()
			);
			$wpdb->query( $sql );

			$sql = sprintf(
				'ALTER TABLE `%s` DROP COLUMN `language`',
				\Podlove\Model\Feed::table_name()
			);
			$wpdb->query( $sql );
			break;	
		case 8:
			$sql = sprintf(
				'ALTER TABLE `%s` ADD COLUMN `supports_cover_art` INT',
				\Podlove\Model\Show::table_name()
			);
			$wpdb->query( $sql );
			break;
		case 9:
			// huge architecture migration
			
			// assume first show will be blueprint for the podcast
			$show  = $wpdb->get_row(
				sprintf(
					'SELECT * FROM %s LIMIT 1',
					$wpdb->prefix . 'podlove_show'
				),
				ARRAY_A
			);
			$show_id = $show['id'];

			// On my local machine the migration runs twice.
			// This is a quick fix. caveat: someone who has no show defined
			// will need to uninstall the plugin. That seems acceptable.
			if ( ! $show_id )
				return;

			// all releases of this show will be converted to episodes
			$releases = $wpdb->get_results(
				sprintf(
					'
					SELECT
						E.post_id, R.episode_id, R.active, R.enable, R.slug, R.duration, R.cover_art, R.chapters
					FROM 
						%s R
						INNER JOIN %s E ON R.episode_id = E.id
					WHERE
						R.show_id = "%s"
					',
					$wpdb->prefix . 'podlove_release',
					$wpdb->prefix . 'podlove_episode',
					$show_id
				),
				ARRAY_A
			);

			// write show settings to podcast
			$podcast = \Podlove\Model\Podcast::get_instance();
			foreach ( $show as $key => $value ) {
				$podcast->$key = $value;
			}
			$podcast->save();

			// rebuild show table
			\Podlove\Model\Show::destroy();
			\Podlove\Model\Show::build();
			
			// rebuild episodes table
			\Podlove\Model\Episode::destroy();
			\Podlove\Model\Episode::build();
			foreach ( $releases as $release ) {
				$episode = new \Podlove\Model\Episode();
				foreach ( $release as $key => $value ) {
					if ( ! in_array( $key, array( 'episode_id' ) ) ) {
						$episode->$key = $value;
					}
				}
				$episode->save();
			}

			// clean feed table
			$sql = sprintf(
				'DELETE FROM `%s` WHERE `show_id` != "%s"',
				\Podlove\Model\Feed::table_name(),
				$show_id
			);
			$wpdb->query( $sql );

			$sql = sprintf(
				'ALTER TABLE `%s` DROP COLUMN `show_id`',
				\Podlove\Model\Feed::table_name()
			);
			$wpdb->query( $sql );

			// fix mediafile table
			$sql = sprintf(
				'ALTER TABLE `%s` CHANGE `release_id` `episode_id` INT',
				\Podlove\Model\MediaFile::table_name()
			);
			$wpdb->query( $sql );
		break;

	}

}

