<?php
namespace Podlove\Modules\Migration\Settings\Wizard;
use Podlove\Modules\Migration\Settings\Assistant;
use Podlove\Modules\Migration\Enclosure;
use Podlove\Modules\Migration\Legacy_Post_Parser;
use Podlove\Model;

class StepMigrate extends Step {

	public $title = 'Migrate';
	
	public function template() {
		?>
		<div class="row-fluid">
			<div class="span12">
				<div class="well">
					Migrating ...
				</div>
			</div>
		</div>
		<?php
		// default to preview-page
		$current_step = (int) $_REQUEST['step'];
		\Podlove\Modules\Migration\Migration::instance()->update_module_option( 'current_step', $current_step - 1 );

		// then begin to migrate
		$migration_settings = get_option( 'podlove_migration', array() );

		// Basic Podcast Settings
		$podcast = Model\Podcast::get_instance();
		$podcast->title                = $migration_settings['podcast']['title'];
		$podcast->subtitle             = $migration_settings['podcast']['subtitle'];
		$podcast->summary              = $migration_settings['podcast']['summary'];
		$podcast->media_file_base_uri  = $migration_settings['podcast']['media_file_base_url'];
		$podcast->save();

		// Create Assets
		$assets = array();
		foreach ( $migration_settings['file_types'] as $file_type_id => $_ ) {
			$file_type = Model\FileType::find_one_by_id( $file_type_id );
			$is_image = in_array( $file_type->extension, array( 'png', 'jpg', 'jpeg', 'gif' ) );

			$asset = new Model\EpisodeAsset();
			$asset->title = $file_type->name;
			$asset->file_type_id = $file_type_id;
			$asset->downloadable = !$is_image;
			$asset->save();
			$assets[] = $asset;

			if ( $is_image ) {
				$asset_assignments = get_option( 'podlove_asset_assignment', array() );
				$asset_assignments['image'] = $asset->id;
				update_option( 'podlove_asset_assignment', $asset_assignments );
			}

			// create feeds
			if ( stripos( $file_type->mime_type, 'audio' ) !== false ) {
				$feed = new Model\Feed();
				$feed->episode_asset_id = $asset->id;
				$feed->name         = $file_type->extension . ' Feed';
				$feed->title        = $file_type->extension . ' Feed';
				$feed->slug         = $file_type->extension;
				$feed->format       = 'rss';
				$feed->enable       = true;
				$feed->discoverable = true;
				$feed->limit_items  = -1;
				$feed->save();
			}
		}

		// Create Episodes
		foreach ( $migration_settings['episodes'] as $post_id => $_ ) {
			$post = get_post( $post_id );

			$new_post = array(
				'menu_order'     => $post->menu_order,
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $post->post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_mime_type' => $post->post_mime_type,
				'post_parent'    => $post_id,
				'post_password'  => $post->post_password,
				'post_status'    => $post->post_status,
				'post_title'     => $post->post_title,
				'post_type'      => 'podcast',
				'post_date'      => $post->post_date,
				'post_date_gmt'  => get_gmt_from_date( $post->post_date )
			);

			$new_post_id = wp_insert_post( $new_post );
			$new_post = get_post( $new_post_id );

			// update guid
			update_post_meta( $new_post_id, '_podlove_guid', $post->guid );

			echo "<strong>" . $new_post->post_title . "</strong><br>";
			flush();

			$post_data = new Legacy_Post_Parser( $post_id );

			$episode = Model\Episode::find_or_create_by_post_id( $new_post_id );
			$episode->slug = Assistant::get_episode_slug( $post, $migration_settings['slug'] );
			$episode->duration = $post_data->get_duration();
			$episode->subtitle = $post_data->get_subtitle();
			$episode->summary = $post_data->get_summary();
			$episode->save();

			foreach ( $assets as $asset ) {
				$file = Model\MediaFile::find_or_create_by_episode_id_and_episode_asset_id( $episode->id, $asset->id );
				echo $file->get_file_url() . "<br>";
				flush();
			}

			// copy all meta
			$meta = get_post_meta( $post_id );
			foreach ( $meta as $key => $values ) {
				foreach ( $values as $value ) {
					add_post_meta( $new_post_id, $key, $value );
				}
			}

			// copy all comments
			foreach ( get_comments( array( 'post_id' => $post_id ) ) as $comment ) {
				$comment->comment_post_ID = $new_post_id;
				wp_insert_comment( (array) $comment );
			}
		}
	}

}