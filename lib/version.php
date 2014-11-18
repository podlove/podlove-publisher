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

define( __NAMESPACE__ . '\DATABASE_VERSION', 85 );

add_action( 'admin_init', '\Podlove\maybe_run_database_migrations' );
add_action( 'admin_init', '\Podlove\run_database_migrations', 5 );

function maybe_run_database_migrations() {

	$database_version = get_option('podlove_database_version');

	if ( $database_version === false ) {
		// plugin has just been installed
		update_option( 'podlove_database_version', DATABASE_VERSION );
	} elseif ( $database_version < DATABASE_VERSION ) {
		wp_redirect( admin_url( 'index.php?podlove_page=podlove_upgrade&_wp_http_referer=' . urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
		exit;
	}
}

function run_database_migrations() {
	global $wpdb;
	
	if (!isset($_REQUEST['podlove_page']) || $_REQUEST['podlove_page'] != 'podlove_upgrade')
		return;

	$database_version = get_option('podlove_database_version');

	if ($database_version >= DATABASE_VERSION)
		return;

	if (is_multisite()) {
		set_time_limit(0); // may take a while, depending on network size
		$current_blog = $wpdb->blogid;
		$blogids = $wpdb->get_col( "SELECT blog_id FROM " . $wpdb->blogs );
		foreach ($blogids as $blog_id) {
			switch_to_blog($blog_id);
			if (is_plugin_active(basename(\Podlove\PLUGIN_DIR) . '/' . \Podlove\PLUGIN_FILE_NAME)) {
				migrate_for_current_blog();
			}
		}
		switch_to_blog($current_blog);
	} else {
		migrate_for_current_blog();
	}

	if (isset($_REQUEST['_wp_http_referer']) && $_REQUEST['_wp_http_referer']) {
		wp_redirect($_REQUEST['_wp_http_referer']);
		exit;
	}
}

function migrate_for_current_blog() {
	$database_version = get_option('podlove_database_version');

	for ($i = $database_version+1; $i <= DATABASE_VERSION; $i++) { 
		\Podlove\run_migrations_for_version($i);
		update_option('podlove_database_version', $i);
	}

	// flush rewrite rules after migrations
	set_transient( 'podlove_needs_to_flush_rewrite_rules', true );

	// purge cache after migrations
	$cache = \Podlove\Cache\TemplateCache::get_instance();
	$cache->setup_purge();
}

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
			if (\Podlove\Modules\Base::is_active('contributors')) {

				// manually trigger activation if the old module was active
				$module = \Podlove\Modules\Contributors\Contributors::instance();
				$module->was_activated('contributors');

				// then, migrate existing contributors
				// register old taxonomy so it can be queried
				$args = array(
					'hierarchical'  => false,
					'labels'        => array(),
					'show_ui'       => true,
					'show_tagcloud' => true,
					'query_var'     => true,
					'rewrite'       => array( 'slug' => 'contributor' ),
				);

				register_taxonomy( 'podlove-contributors', 'podcast', $args );
				$contributor_settings = get_option( 'podlove_contributors', array() );

				$contributors = get_terms( 'podlove-contributors', array( 'hide_empty' => 0 ) );

				if ($contributors && !is_wp_error($contributors) && \Podlove\Modules\Contributors\Model\Contributor::count() == 0) {
					foreach ($contributors as $contributor) {

						// create new contributor
						$new = new \Podlove\Modules\Contributors\Model\Contributor();
						$new->publicname = $contributor->name;
						$new->realname = $contributor->name;
						$new->slug = $contributor->slug;
						$new->showpublic = true;

						if (isset($contributor_settings[$contributor->term_id]['contributor_email'])) {
							$email = $contributor_settings[$contributor->term_id]['contributor_email'];
							if ($email) {
								$new->privateemail = $email;
								$new->avatar = $email;
							}
						}
						$new->save();

						// create contributions
						$query = new \WP_Query(array(
							'posts_per_page' => -1,
							'post_type' => 'podcast',
							'tax_query' => array(
								array(
									'taxonomy' => 'podlove-contributors',
									'field' => 'slug',
									'terms' => $contributor->slug
								)
							)
						));
						while ($query->have_posts()) {
							$post = $query->next_post();
							$contribution = new \Podlove\Modules\Contributors\Model\EpisodeContribution();
							$contribution->contributor_id = $new->id;
							$contribution->episode_id = Model\Episode::find_one_by_post_id($post->ID)->id;
							$contribution->save();
						}
					}
				}
			}
		break;
		case 47:
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `protected` TINYINT(1) NULL',
				\Podlove\Model\Feed::table_name()
			) );
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `protection_type` TINYINT(1)',
				\Podlove\Model\Feed::table_name()
			) );
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `protection_user` VARCHAR(60)',
				\Podlove\Model\Feed::table_name()
			) );
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `protection_password` VARCHAR(64)',
				\Podlove\Model\Feed::table_name()
			) );
		break;
		case 48:
			$podcast = Model\Podcast::get_instance();
			$podcast->limit_items = '-1';
			$podcast->save();
		break;
		case 49:
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `explicit` TINYINT',
				Model\Episode::table_name()
			) );
		break;
		case 50:
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
		case 51:
			if (\Podlove\Modules\Base::is_active('contributors')) {
				
				\Podlove\Modules\Contributors\Model\ContributorGroup::build();

				$wpdb->query( sprintf(
					'ALTER TABLE `%s` ADD COLUMN `group_id` VARCHAR(255) AFTER `role_id`',
					\Podlove\Modules\Contributors\Model\EpisodeContribution::table_name()
				) );
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` ADD COLUMN `group_id` VARCHAR(255) AFTER `role_id`',
					\Podlove\Modules\Contributors\Model\ShowContribution::table_name()
				) );
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` ADD COLUMN `paypal` VARCHAR(255) AFTER `flattr`',
					\Podlove\Modules\Contributors\Model\Contributor::table_name()
				) );
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` ADD COLUMN `bitcoin` VARCHAR(255) AFTER `paypal`',
					\Podlove\Modules\Contributors\Model\Contributor::table_name()
				) );
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` ADD COLUMN `litecoin` VARCHAR(255) AFTER `bitcoin`',
					\Podlove\Modules\Contributors\Model\Contributor::table_name()
				) );
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` DROP COLUMN `permanentcontributor`',
					\Podlove\Modules\Contributors\Model\Contributor::table_name()
				) );
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` DROP COLUMN `role`',
					\Podlove\Modules\Contributors\Model\Contributor::table_name()
				) );
			}
		break;
		case 52:
			if (\Podlove\Modules\Base::is_active('contributors')) {
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` ADD COLUMN `jobtitle` VARCHAR(255) AFTER `department`',
					\Podlove\Modules\Contributors\Model\Contributor::table_name()
				) );
			}
		break;
		case 53:
			// set all Episode as published (fix for ADN Module)
			$episodes = Model\Episode::all();
			foreach ( $episodes as $episode ) {
				$post = get_post( $episode->post_id );
				if ( $post->post_status == 'publish' )
					update_post_meta( $episode->post_id, '_podlove_episode_was_published', true );
			}
		break;
		case 54:
			if (\Podlove\Modules\Base::is_active('contributors')) {
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` ADD COLUMN `googleplus` TEXT AFTER `ADN`',
					\Podlove\Modules\Contributors\Model\Contributor::table_name()
				) );
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` CHANGE COLUMN `showpublic` `visibility` TINYINT(1)',
					\Podlove\Modules\Contributors\Model\Contributor::table_name()
				) );
			}
		break;
		case 55:
			if (\Podlove\Modules\Base::is_active('contributors')) {
				\Podlove\Modules\Contributors\Model\DefaultContribution::build();

				$wpdb->query( sprintf(
					'ALTER TABLE `%s` ADD COLUMN `comment` TEXT AFTER `position`',
					\Podlove\Modules\Contributors\Model\EpisodeContribution::table_name()
				) );
				$wpdb->query( sprintf(
					'ALTER TABLE `%s` ADD COLUMN `comment` TEXT AFTER `position`',
					\Podlove\Modules\Contributors\Model\ShowContribution::table_name()
				) );
			}
		break;
		case 56:
			// migrate Podcast Contributors to Default Contributors
			if (\Podlove\Modules\Base::is_active('contributors')) {
				$podcast_contributors = \Podlove\Modules\Contributors\Model\ShowContribution::all();
				foreach ($podcast_contributors as $podcast_contributor_key => $podcast_contributor) {
					$new = new \Podlove\Modules\Contributors\Model\DefaultContribution();
					$new->contributor_id = $podcast_contributor->contributor_id;
					$new->group_id = $podcast_contributor->group_id;
					$new->role_id = $podcast_contributor->role_id;
					$new->position = $podcast_contributor->positon;
					$new->save();
				}
			}
		break;
		case 57:
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` ADD COLUMN `append_name_to_podcast_title` TINYINT(1) NULL AFTER `embed_content_encoded`',
				\Podlove\Model\Feed::table_name()
			) );
		break;
		case 58:
			// if contributors module is active, activate social module
			if (\Podlove\Modules\Base::is_active('contributors')) {
				\Podlove\Modules\Base::activate('social');
			}
		break;
		case 59:
			if (\Podlove\Modules\Base::is_active('bitlove')) {
				$wpdb->query( sprintf(
					"ALTER TABLE `%s` ADD COLUMN `bitlove` TINYINT(1) DEFAULT '0'",
					\Podlove\Model\Feed::table_name()
				) );
			}
		break;
		case 60:
			\Podlove\Modules\Base::activate('oembed');
			\Podlove\Modules\Base::activate('feed_validation');
		break;
		case 61:
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` DROP COLUMN `publication_date`',
				Model\Episode::table_name()
			) );
		break;
		case 62:
			// rename column
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` CHANGE COLUMN `record_date` `recording_date` DATETIME',
				Model\Episode::table_name()
			) );

			// update settings
			$meta = get_option( 'podlove_metadata' );

			if (isset($meta['enable_episode_publication_date'])) {
				unset($meta['enable_episode_publication_date']);
			}

			if (isset($meta['enable_episode_record_date'])) {
				$meta['enable_episode_recording_date'] = $meta['enable_episode_record_date'];
				unset($meta['enable_episode_record_date']);
			}

			update_option('podlove_metadata', $meta);
		break;
		case 63:
			if (\Podlove\Modules\Base::is_active('social')) {
				$tumblr_service = \Podlove\Modules\Social\Model\Service::find_one_by_property( 'title', 'Tumblr' );
				$tumblr_service->url_scheme = 'http://%account-placeholder%.tumblr.com/';
				$tumblr_service->save();
			}
		break;
		case 64:
			if (\Podlove\Modules\Base::is_active('social')) {
				$services = array(
					array(
							'title' 		=> '500px',
							'type'			=> 'social',
							'description'	=> '500px Account',
							'logo'			=> '500px-128.png',
							'url_scheme'	=> 'https://500px.com/%account-placeholder%'
						),
					array(
							'title' 		=> 'Last.fm',
							'type'			=> 'social',
							'description'	=> 'Last.fm Account',
							'logo'			=> 'lastfm-128.png',
							'url_scheme'	=> 'https://www.lastfm.de/user/%account-placeholder%'
						),
					array(
							'title' 		=> 'OpenStreetMap',
							'type'			=> 'social',
							'description'	=> 'OpenStreetMap Account',
							'logo'			=> 'openstreetmap-128.png',
							'url_scheme'	=> 'https://www.openstreetmap.org/user/%account-placeholder%'
						),
					array(
							'title' 		=> 'Soup',
							'type'			=> 'social',
							'description'	=> 'Soup Account',
							'logo'			=> 'soup-128.png',
							'url_scheme'	=> 'http://%account-placeholder%.soup.io'
						)
				);

				foreach ($services as $service_key => $service) {
					$c = new \Podlove\Modules\Social\Model\Service;
					$c->title = $service['title'];
					$c->type = $service['type'];
					$c->description = $service['description'];
					$c->logo = $service['logo'];
					$c->url_scheme = $service['url_scheme'];
					$c->save();
				}
			}
		break;
		case 65:
			if (\Podlove\Modules\Base::is_active('social')) {
				$flattr_service = \Podlove\Modules\Social\Model\Service::find_one_by_where( "`title` = 'Flattr' AND `type` = 'donation'" );
				if ($flattr_service) {
					$contributor_flattr_donations_accounts = \Podlove\Modules\Social\Model\ContributorService::find_all_by_property( 'service_id', $flattr_service->id );

					foreach ( $contributor_flattr_donations_accounts as $contributor_flattr_donations_account ) {
						$contributor = \Podlove\Modules\Contributors\Model\Contributor::find_by_id( $contributor_flattr_donations_account->contributor_id );
						
						if ( $contributor && is_null( $contributor->flattr ) ) {
							$contributor->flattr = $contributor_flattr_donations_account->value;
							$contributor->save();
						}

						$contributor_flattr_donations_account->delete();

					}

					$flattr_service->delete();
				}
			}
		break;
		case 66:
			// Temporary add license_type and CC license fields to episode model
			\Podlove\Model\Episode::property( 'license_type', 'VARCHAR(255)' );
			\Podlove\Model\Episode::property( 'license_cc_allow_modifications', 'VARCHAR(255)' );
			\Podlove\Model\Episode::property( 'license_cc_allow_commercial_use', 'VARCHAR(255)' );
			\Podlove\Model\Episode::property( 'license_cc_license_jurisdiction', 'VARCHAR(255)' );

			$podcast  = \Podlove\Model\Podcast::get_instance();
			$episodes = \Podlove\Model\Episode::all();

			// Migration for Podcast
			if( $podcast->license_type  == 'cc' && $podcast->license_cc_allow_commercial_use !== '' &&
				$podcast->license_cc_allow_modifications !== '' && $podcast->license_cc_license_jurisdiction !== '' ) {
					$license = array(
							'version'		=>	'3.0',
							'commercial_use'=>	$podcast->license_cc_allow_commercial_use,
							'modification'	=>	$podcast->license_cc_allow_modifications,
							'jurisdiction'	=>	$podcast->license_cc_license_jurisdiction
									);

					$podcast->license_url = \Podlove\Model\License::get_url_from_license( $license );
					$podcast->license_name = \Podlove\Model\License::get_name_from_license( $license );

					$podcast->save();
			}

			// Migration for Episodes
			foreach ( $episodes as $episode ) {
				if( $episode->license_type  == 'other' || $episode->license_cc_allow_commercial_use == '' ||
					$episode->license_cc_allow_modifications == '' || $episode->license_cc_license_jurisdiction == '' ) {
					continue;
				}

				$license = array(
					'version'        => '3.0',
					'commercial_use' => $episode->license_cc_allow_commercial_use,
					'modification'   => $episode->license_cc_allow_modifications,
					'jurisdiction'   => $episode->license_cc_license_jurisdiction
				);

				$episode->license_url  = \Podlove\Model\License::get_url_from_license( $license );
				$episode->license_name = \Podlove\Model\License::get_name_from_license( $license );

				$episode->save();
			}
		break;
		case 67:
			if (\Podlove\Modules\Base::is_active('social')) {
				$instagram_service = \Podlove\Modules\Social\Model\Service::find_one_by_where( "`title` = 'Instagram' AND `type` = 'social'" );
				if ($instagram_service) {
					$instagram_service->url_scheme = 'https://instagram.com/%account-placeholder%';
					$instagram_service->save();
				}
			}
		break;
		case 68: // Do that ADN module fix again, as we forgot to mark all episodes as published if the ADN module is activated
			$episodes = Model\Episode::all();
			foreach ( $episodes as $episode ) {
				$post = get_post( $episode->post_id );
				if ( $post->post_status == 'publish' && !get_post_meta( $episode->post_id, '_podlove_episode_was_published', true ) )
						update_post_meta( $episode->post_id, '_podlove_episode_was_published', true );
			}
		break;
		case 69:
			if (\Podlove\Modules\Base::is_active('app_dot_net')) {
				$adn = \Podlove\Modules\AppDotNet\App_Dot_Net::instance();
				if ( $adn->get_module_option( 'adn_auth_key' ) )
					$adn->update_module_option( 'adn_automatic_announcement', 'on' );
			}
		break;
		case 70:
			\Podlove\Model\DownloadIntent::build();
			\Podlove\Model\UserAgent::build();
		break;
		case 71:
			// update for everyone, so even those with inactive service tables get updated
			$wpdb->query( sprintf(
				'ALTER TABLE `%s` CHANGE COLUMN `type` `category` VARCHAR(255)',
				\Podlove\Modules\Social\Model\Service::table_name()
			) );

			$wpdb->query( sprintf(
				"ALTER TABLE `%s` ADD COLUMN `type` VARCHAR(255) AFTER `category`",
				\Podlove\Modules\Social\Model\Service::table_name()
			) );

			$services = \Podlove\Modules\Social\Model\Service::all();
			foreach ($services as $service) {
				$service->type = strtolower($service->title);
				$service->save();
			}
		break;
		case 72:
			if (\Podlove\Modules\Base::is_active('social')) {
				$services = array(
					array(
						'title'       => 'Vimeo',
						'type'        => 'vimeo',
						'category'    => 'social',
						'description' => 'Vimeo Account',
						'logo'        => 'vimeo-128.png',
						'url_scheme'  => 'http://vimeo.com/%account-placeholder%'
					),
					array(
						'title' 		=> 'about.me',
						'type'	 		=> 'about.me',
						'category'		=> 'social',
						'description'	=> 'about.me Account',
						'logo'			=> 'aboutme-128.png',
						'url_scheme'	=> 'http://about.me/%account-placeholder%'
					),
					array(
						'title' 		=> 'Gittip',
						'type'	 		=> 'gittip',
						'category'		=> 'donation',
						'description'	=> 'Gittip Account',
						'logo'			=> 'gittip-128.png',
						'url_scheme'	=> 'https://www.gittip.com/%account-placeholder%'
					)
				);

				foreach ($services as $service_key => $service) {
					$c = new \Podlove\Modules\Social\Model\Service;
					$c->title = $service['title'];
					$c->type = $service['type'];
					$c->category = $service['category'];
					$c->description = $service['description'];
					$c->logo = $service['logo'];
					$c->url_scheme = $service['url_scheme'];
					$c->save();
				}
			}
		break;
		case 73:
			if (\Podlove\Modules\Base::is_active('social')) {
				$jabber_service = \Podlove\Modules\Social\Model\Service::find_one_by_where( "`type` = 'jabber' AND `category` = 'social'" );
				if ($jabber_service) {
					$jabber_service->url_scheme = 'jabber:%account-placeholder%';
					$jabber_service->save();
				}
			}
		break;
		case 74:
			Model\GeoArea::build();
			Model\GeoAreaName::build();
			\Podlove\Geo_Ip::register_updater_cron();
		break;
		case 75:
			$tracking = get_option('podlove_tracking');
			$tracking['mode'] = 0;
			update_option('podlove_tracking', $tracking);
		break;
		case 76:
			set_transient( 'podlove_needs_to_flush_rewrite_rules', true );
		break;
		case 77:
			// delete empty user agents
			$userAgentTable      = Model\UserAgent::table_name();
			$downloadIntentTable = Model\DownloadIntent::table_name();

			$sql = "SELECT
				di.id
			FROM
				$downloadIntentTable di
				JOIN $userAgentTable ua ON ua.id = di.user_agent_id
			WHERE
				ua.user_agent IS NULL";
			$ids = $wpdb->get_col($sql);

			if (is_array($ids) && count($ids)) {
				$sql = "UPDATE $downloadIntentTable SET user_agent_id = NULL WHERE id IN (" . implode(",", $ids) . ")";
				$wpdb->query($sql);

				$sql = "DELETE FROM $userAgentTable WHERE user_agent IS NULL";
				$wpdb->query($sql);
			}
		break;
		case 78:
			if (\Podlove\Modules\Base::is_active('social')) {
				$c = new \Podlove\Modules\Social\Model\Service;
				$c->title = 'Auphonic Credits';
				$c->category = 'donation';
				$c->type = 'auphonic credits';
				$c->description = 'Auphonic Account';
				$c->logo = 'auphonic-128.png';
				$c->url_scheme = 'https://auphonic.com/donate_credits?user=%account-placeholder%';
				$c->save();
			}
		break;
		case 79:
			set_transient( 'podlove_needs_to_flush_rewrite_rules', true );
			$cache = \Podlove\Cache\TemplateCache::get_instance();
			$cache->setup_purge();
		break;
		case 80:
			$sql = sprintf(
				'ALTER TABLE `%s` ADD COLUMN `httprange` VARCHAR(255)',
				\Podlove\Model\DownloadIntent::table_name()
			);
			$wpdb->query( $sql );
		break;
		case 81:
			// remove all caches with old namespace
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE \"_transient_podlove_cache%\"");
		break;
		case 82:
			// set all redirect entries to active
			$redirect_settings = \Podlove\get_setting( 'redirects', 'podlove_setting_redirect' );
			foreach ($redirect_settings as $index => $data) {
				$redirect_settings[$index]['active'] = 'active';
			}
			update_option('podlove_redirects', array( 'podlove_setting_redirect' => $redirect_settings ));
		break;
		case 83:
			\Podlove\Model\DownloadIntentClean::build();
			
			$alterations = array(
				'ALTER TABLE `%s` ADD COLUMN `bot` TINYINT',
				'ALTER TABLE `%s` ADD COLUMN `client_name` VARCHAR(255)',
				'ALTER TABLE `%s` ADD COLUMN `client_version` VARCHAR(255)',
				'ALTER TABLE `%s` ADD COLUMN `client_type` VARCHAR(255)',
				'ALTER TABLE `%s` ADD COLUMN `os_name` VARCHAR(255)',
				'ALTER TABLE `%s` ADD COLUMN `os_version` VARCHAR(255)',
				'ALTER TABLE `%s` ADD COLUMN `device_brand` VARCHAR(255)',
				'ALTER TABLE `%s` ADD COLUMN `device_model` VARCHAR(255)',
			);

			foreach ($alterations as $sql) {
				$wpdb->query( sprintf($sql, Model\UserAgent::table_name()) );
			}

			Model\UserAgent::reparse_all();
		break;
		case 84:
			delete_option('podlove_tpl_cache_keys');
		break;
		case 85:
			add_option('podlove_tracking_delete_head_requests', 1);
		break;
	}

}
