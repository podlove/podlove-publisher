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
use \Podlove\Model;

define( __NAMESPACE__ . '\DATABASE_VERSION', 24 );

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
				\Podlove\Model\EpisodeAsset::table_name()
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
				\Podlove\Model\EpisodeAsset::table_name()
			);
			$row = $wpdb->get_row( $sql );
			if ( strtolower(substr($row->Type, 0, 3)) === 'int' ) {
				$wpdb->query( sprintf(
					'UPDATE `%s` SET title = NULL',
					\Podlove\Model\EpisodeAsset::table_name()
				) );
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` MODIFY COLUMN `title` VARCHAR(255)',
					\Podlove\Model\EpisodeAsset::table_name()
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

			// remove suffix
			$sql = sprintf(
				'ALTER TABLE `%s` DROP COLUMN `suffix`',
				\Podlove\Model\EpisodeAsset::table_name()
			);
			$wpdb->query( $sql );

			// add more default formats
			$default_formats = array(
				array( 'name' => 'PDF Document',  'type' => 'ebook', 'mime_type' => 'application/pdf',  'extension' => 'pdf' ),
				array( 'name' => 'ePub Document', 'type' => 'ebook', 'mime_type' => 'application/epub+zip',  'extension' => 'epub' ),
				array( 'name' => 'PNG Image',     'type' => 'image', 'mime_type' => 'image/png',   'extension' => 'png' ),
				array( 'name' => 'JPEG Image',    'type' => 'image', 'mime_type' => 'image/jpeg',  'extension' => 'jpg' ),
			);
			
			foreach ( $default_formats as $format ) {
				$f = new Model\FileType;
				foreach ( $format as $key => $value ) {
					$f->{$key} = $value;
				}
				$f->save();
			}

			// update assistant
			$assistant = \Podlove\Modules\EpisodeAssistant\Episode_Assistant::instance();
			$template = $assistant->get_module_option( 'title_template' );
			$template = str_replace( '%show_slug%', '%podcast_slug%', $template );
			$assistant->update_module_option( 'title_template', $template );

			// update media locations
			$media_locations = \Podlove\Model\EpisodeAsset::all();
			foreach ( $media_locations as $media_location ) {
				$media_location->url_template = str_replace( '%suffix%', '', $media_location->url_template );
				$media_location->save();
			}
		break;	
		case 10:
			$sql = sprintf(
				'ALTER TABLE `%s` ADD COLUMN `summary` TEXT',
				\Podlove\Model\Episode::table_name()
			);
			$wpdb->query( $sql );
		break;	
		case 11:
			$sql = sprintf(
				'ALTER TABLE `%s` ADD COLUMN `downloadable` INT',
				\Podlove\Model\EpisodeAsset::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 12:
			$sql = sprintf(
				'UPDATE `%s` SET `downloadable` = 1',
				\Podlove\Model\EpisodeAsset::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 13:
			$opus = array( 'name' => 'Opus Audio', 'type' => 'audio', 'mime_type' => 'audio/opus', 'extension' => 'opus' );
			$f = new \Podlove\Model\FileType;
			foreach ( $opus as $key => $value ) {
				$f->{$key} = $value;
			}
			$f->save();
		break;
		case 14:
			$sql = sprintf(
				'ALTER TABLE `%s` RENAME TO `%s`',
				$wpdb->prefix . 'podlove_medialocation',
				\Podlove\Model\EpisodeAsset::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 15:
			$sql = sprintf(
				'ALTER TABLE `%s` CHANGE `media_location_id` `episode_asset_id` INT',
				\Podlove\Model\MediaFile::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 16:
			$sql = sprintf(
				'ALTER TABLE `%s` CHANGE `media_location_id` `episode_asset_id` INT',
				\Podlove\Model\Feed::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 17:
			$sql = sprintf(
				'ALTER TABLE `%s` RENAME TO `%s`',
				$wpdb->prefix . 'podlove_mediaformat',
				\Podlove\Model\FileType::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 18:
			$sql = sprintf(
				'ALTER TABLE `%s` CHANGE `media_format_id` `file_type_id` INT',
				\Podlove\Model\EpisodeAsset::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 19:
			\Podlove\Model\Template::build();
		break;
		case 20:
			$sql = sprintf(
				'ALTER TABLE `%s` ADD COLUMN `suffix` VARCHAR(255)',
				\Podlove\Model\EpisodeAsset::table_name()
			);
			$wpdb->query( $sql );
			$sql = sprintf(
				'ALTER TABLE `%s` DROP COLUMN `url_template`',
				\Podlove\Model\EpisodeAsset::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 21:
			$podcast = Model\Podcast::get_instance();
			$podcast->url_template = '%media_file_base_url%%episode_slug%%suffix%.%format_extension%';
			$podcast->save();
		break;
		case 22:
			$sql = sprintf(
				'ALTER TABLE `%s` ADD COLUMN `redirect_http_status` INT AFTER `redirect_url`',
				Model\Feed::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 23:
			$sql = sprintf(
				'ALTER TABLE `%s` DROP COLUMN `show_description`',
				Model\Feed::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 24:
			$podcast = Model\Podcast::get_instance();
			update_option( 'podlove_asset_assignment', array(
				'image'    => $podcast->supports_cover_art,
				'chapters' => $podcast->chapter_file
			) );
		break;

	}

}
