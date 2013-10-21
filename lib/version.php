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

define( __NAMESPACE__ . '\DATABASE_VERSION', 46 );

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
		case 25:
			// rename meta podlove_guid to _podlove_guid
			$episodes = Model\Episode::all();
			foreach ( $episodes as $episode ) {
				$post = get_post( $episode->post_id );

				// skip revisions
				if ( $post->post_status == 'inherit' )
					continue;

				$guid = get_post_meta( $episode->post_id, 'podlove_guid', true );

				if ( ! $guid )
					$guid = $post->guid;
				
				delete_post_meta( $episode->post_id, 'podlove_guid' );
				update_post_meta( $episode->post_id, '_podlove_guid', $guid );
			}
		break;
		case 26:
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` MODIFY COLUMN `subtitle` TEXT',
				Model\Episode::table_name()
			) );
		break;
		case 27:
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `record_date` DATETIME AFTER `chapters`',
				Model\Episode::table_name()
			) );
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `publication_date` DATETIME AFTER `record_date`',
				Model\Episode::table_name()
			) );
		break;
		case 28:
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `position` FLOAT AFTER `downloadable`',
				Model\EpisodeAsset::table_name()
			) );
			$wpdb->query( sprintf(
				'UPDATE `%s` SET position = id',
				Model\EpisodeAsset::table_name()
			) );
		break;
		case 29:
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `embed_content_encoded` INT AFTER `limit_items`',
				Model\Feed::table_name()
			) );
		break;
		case 30:
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` MODIFY `autoinsert` VARCHAR(255)',
				Model\Template::table_name()
			) );
		break;
		case 32:
			flush_rewrite_rules();
		break;
		case 33:
			$apd = array( 'name' => 'Auphonic Production Description', 'type' => 'metadata', 'mime_type' => 'application/json',  'extension' => 'json' );
			$f = new \Podlove\Model\FileType;
			foreach ( $apd as $key => $value ) {
				$f->{$key} = $value;
			}
			$f->save();
		break;
		case 34:
			$options = get_option( 'podlove', array() );
			if ( !array_key_exists( 'episode_archive', $options ) ) $options['episode_archive'] = 'on';
			if ( !array_key_exists( 'episode_archive_slug', $options ) ) $options['episode_archive_slug'] = '/podcast/';
			if ( !array_key_exists( 'use_post_permastruct', $options ) ) $options['use_post_permastruct'] = 'off';
			if ( !array_key_exists( 'custom_episode_slug', $options ) ) $options['custom_episode_slug'] = '/podcast/%podcast%/';
			else $options['custom_episode_slug'] = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', $options['custom_episode_slug'] ) );
			update_option( 'podlove', $options );
		break;
		case 35:
			Model\Feed::build_indices();
			Model\FileType::build_indices();
			Model\EpisodeAsset::build_indices();
			Model\MediaFile::build_indices();
			Model\Episode::build_indices();
			Model\Template::build_indices();
		break;
		case 36:
		$wpdb->query( sprintf(
			'ALTER TABLE `%s` ADD COLUMN `etag` VARCHAR(255)',
			Model\MediaFile::table_name()
		) );
		break;
		case 37:
			\Podlove\Modules\Base::activate( 'asset_validation' );
		break;
		case 38:
			\Podlove\Modules\Base::activate( 'logging' );
		break;
		case 39:
			// migrate previous template autoinsert settings
			$assignments = Model\TemplateAssignment::get_instance();
			$results = $wpdb->get_results(
				sprintf( 'SELECT * FROM `%s`', Model\Template::table_name() )
			);

			foreach ( $results as $template ) {
				if ( $template->autoinsert == 'beginning' ) {
					$assignments->top = $template->id;
				} elseif ( $template->autoinsert == 'end' ) {
					$assignments->bottom = $template->id;
				}
			}

			$assignments->save();

			// remove template autoinsert column
			$sql = sprintf(
				'ALTER TABLE `%s` DROP COLUMN `autoinsert`',
				\Podlove\Model\Template::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 40:
			$wpdb->query( sprintf(
				'UPDATE `%s` SET position = id WHERE position IS NULL',
				Model\EpisodeAsset::table_name()
			) );
		break;
		case 41:
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `position` FLOAT AFTER `slug`',
				Model\Feed::table_name()
			) );
			$wpdb->query( sprintf(
				'UPDATE `%s` SET position = id',
				Model\Feed::table_name()
			) );
		break;
		case 42:
			$wpdb->query(
				'DELETE FROM `' . $wpdb->options . '` WHERE option_name LIKE "%podlove_chapters_string_%"'
			);
		break;
		case 43:
			$podlove_options = get_option( 'podlove', array() );

			$podlove_website = array(
				'merge_episodes'         => isset( $podlove_options['merge_episodes'] ) ? $podlove_options['merge_episodes'] : false,
				'hide_wp_feed_discovery' => isset( $podlove_options['hide_wp_feed_discovery'] ) ? $podlove_options['hide_wp_feed_discovery'] : false,
				'use_post_permastruct'   => isset( $podlove_options['use_post_permastruct'] ) ? $podlove_options['use_post_permastruct'] : false,
				'custom_episode_slug'    => isset( $podlove_options['custom_episode_slug'] ) ? $podlove_options['custom_episode_slug'] : '/episode/%podcast%',
				'episode_archive'        => isset( $podlove_options['episode_archive'] ) ? $podlove_options['episode_archive'] : false,
				'episode_archive_slug'   => isset( $podlove_options['episode_archive_slug'] ) ? $podlove_options['episode_archive_slug'] : '/podcast/',
				'url_template'           => isset( $podlove_options['url_template'] ) ? $podlove_options['url_template'] : '%media_file_base_url%%episode_slug%%suffix%.%format_extension%'
			);
			$podlove_metadata = array(
				'enable_episode_record_date'      => isset( $podlove_options['enable_episode_record_date'] ) ? $podlove_options['enable_episode_record_date'] : false,
				'enable_episode_publication_date' => isset( $podlove_options['enable_episode_publication_date'] ) ? $podlove_options['enable_episode_publication_date'] : false
			);
			$podlove_redirects = array(
				'podlove_setting_redirect' => isset( $podlove_options['podlove_setting_redirect'] ) ? $podlove_options['podlove_setting_redirect'] : array(),
			);

			add_option( 'podlove_website', $podlove_website );
			add_option( 'podlove_metadata', $podlove_metadata );
			add_option( 'podlove_redirects', $podlove_redirects );
		break;
		case 44:
			$wpdb->query(
				'DELETE FROM `' . $wpdb->postmeta . '` WHERE meta_key = "last_validated_at"'
			);
		break;
		case 45:
			delete_transient('podlove_auphonic_user');
			delete_transient('podlove_auphonic_presets');
		break;
		case 46:
			$podcast = Model\Podcast::get_instance();
			$podcast->license_type = 'other';
			$podcast->save();

			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `license_type` VARCHAR(255) AFTER `publication_date`',
				Model\Episode::table_name()
			) );
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `license_name` TEXT AFTER `license_type`',
				Model\Episode::table_name()
			) );
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `license_url` TEXT AFTER `license_name`',
				Model\Episode::table_name()
			) );
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `license_cc_allow_modifications` TEXT AFTER `license_url`',
				Model\Episode::table_name()
			) );
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `license_cc_allow_commercial_use` TEXT AFTER `license_cc_allow_modifications`',
				Model\Episode::table_name()
			) );
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `license_cc_license_jurisdiction` TEXT AFTER `license_cc_allow_commercial_use`',
				Model\Episode::table_name()
			) );
		break;
	}

}
