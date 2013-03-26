<?php
namespace Podlove\Modules\Migration\Settings\Wizard;
use Podlove\Modules\Migration\Settings\Assistant;
use Podlove\Modules\Migration\Enclosure;
use Podlove\Modules\Migration\Legacy_Post_Parser;
use Podlove\Modules\Migration;
use Podlove\Model;

class StepMigrate extends Step {

	public $title = 'Migrate';
	
	public function template() {

		// load already migrated posts
		$migrated_posts_cache = get_option( 'podlove_migrated_posts_cache', array() );

		// then begin to migrate
		$migration_settings = get_option( 'podlove_migration', array() );


		// Basic Podcast Settings
		$podcast = Model\Podcast::get_instance();
		$podcast->title                = $migration_settings['podcast']['title'];
		$podcast->subtitle             = $migration_settings['podcast']['subtitle'];
		$podcast->summary              = $migration_settings['podcast']['summary'];
		$podcast->media_file_base_uri  = \Podlove\Modules\Migration\get_media_file_base_url();

		// harvest low hanging podPress fruits
		if ( $podPress_config = get_option( 'podPress_config' ) ) {
			if ( isset( $podPress_config['iTunes']['image'] ) && ! $podcast->cover_image ) {
				$podcast->cover_image = $podPress_config['iTunes']['image'];
			}
		}

		// harvest low hanging PowerPress fruits
		if ( $powerPress_config = get_option( 'powerpress_feed' ) ) {
			if ( isset( $powerPress_config['itunes_image'] ) && ! $podcast->cover_image ) {
				$podcast->cover_image = $powerPress_config['itunes_image'];
			}
			if ( isset( $powerPress_config['itunes_cat_1'] ) && ! $podcast->category_1 ) {
				$podcast->category_1 = $powerPress_config['itunes_cat_1'];
			}
			if ( isset( $powerPress_config['itunes_cat_2'] ) && ! $podcast->category_2 ) {
				$podcast->category_2 = $powerPress_config['itunes_cat_2'];
			}
			if ( isset( $powerPress_config['itunes_cat_3'] ) && ! $podcast->category_3 ) {
				$podcast->category_3 = $powerPress_config['itunes_cat_3'];
			}
		}

		$podcast->save();

		// Create Assets
		$assets = array();
		foreach ( $migration_settings['file_types'] as $file_type_id => $_ ) {
			$file_type = Model\FileType::find_one_by_id( $file_type_id );
			$is_image = in_array( $file_type->extension, array( 'png', 'jpg', 'jpeg', 'gif' ) );

			$asset = Model\EpisodeAsset::find_one_by_file_type_id( $file_type_id );
			if ( ! $asset ) {
				$asset = new Model\EpisodeAsset();
				$asset->title = $file_type->name;
				$asset->file_type_id = $file_type_id;
				$asset->downloadable = !$is_image;
				$asset->save();
			}
			$assets[] = $asset;

			if ( $is_image ) {
				$asset_assignments = get_option( 'podlove_asset_assignment', array() );
				if ( ! $asset_assignments['image'] ) {
					$asset_assignments['image'] = $asset->id;
					update_option( 'podlove_asset_assignment', $asset_assignments );
				}
			}

			// create feeds
			if ( stripos( $file_type->mime_type, 'audio' ) !== false ) {
				$feed = Model\Feed::find_one_by_episode_asset_id( $asset->id );
				if ( ! $feed ) {
					$feed = new Model\Feed();
					$feed->episode_asset_id = $asset->id;
					$feed->name         = $file_type->extension . ' Feed';
					$feed->title        = $file_type->name;
					$feed->slug         = $file_type->extension;
					$feed->format       = 'rss';
					$feed->enable       = true;
					$feed->discoverable = true;
					$feed->limit_items  = -1;
					$feed->save();
				}
			}

			// set web player settings
			$webplayer_formats = get_option( 'podlove_webplayer_formats', array() );
			if ( ! isset( $webplayer_formats['audio'] ) ) 
				$webplayer_formats['audio'] = array();

			if ( stripos( $file_type->mime_type, 'audio/mpeg' ) !== false ) {
				$webplayer_formats['audio']['mp3'] = $asset->id;
			} elseif ( stripos( $file_type->mime_type, 'audio/mp4' ) !== false ) {
				$webplayer_formats['audio']['mp4'] = $asset->id;
			} elseif ( stripos( $file_type->mime_type, 'audio/ogg' ) !== false ) {
				$webplayer_formats['audio']['ogg'] = $asset->id;
			} elseif ( stripos( $file_type->mime_type, 'audio/opus' ) !== false ) {
				$webplayer_formats['audio']['opus'] = $asset->id;
			}
			update_option( 'podlove_webplayer_formats', $webplayer_formats );
		}

		?>
		
		<form action="" method="POST">
			<input type="submit" name="prev" class="btn" value="<?php echo __( 'Back', 'podlove' ) ?>">
			<input type="button" id="start_migration_button" class="btn btn-primary" value="<?php echo __( 'Start Migration', 'podlove' ) ?>">
			<input type="submit" name="next" id="continue_to_finish_button" class="btn btn-primary disabled pull-right" value="<?php echo __( 'Continue to last step', 'podlove' ) ?>">
		</form>

		<div class="row-fluid">
			<div class="span12">
				<h3 id="migration-header">Migrating <small></small></h3>
			</div>
		</div>

		<div class="progress progress-striped active" id="migration_progress">
			<div class="bar" style="width:0%"></div>
		</div>

		<table class="table table-condensed" id="posts_to_migrate">
			<thead>
				<tr>
					<th>Status</th>
					<th>Episode</th>
				</tr>
			</thead>
			<tbody>
				<?php $migrated_post_ids = array_keys( $migrated_posts_cache ); ?>
				<?php foreach ( $migration_settings['episodes'] as $post_id => $_ ): ?>
					<?php $done = in_array($post_id, $migrated_post_ids); ?>
					<tr data-post-id="<?php echo $post_id ?>" <?php echo ($done) ? 'class="done"' : '' ?>>
						<td class="status">
							<span class="waiting" <?php echo (!$done) ? '' : 'style="display:none"' ?>>waiting ...</span>
							<span class="migrating" style="display:none">migrating ...</span>
							<span class="done" <?php echo ($done) ? '' : 'style="display:none"' ?>><span style="color: green">✓</span></span>
						</td>
						<td class="episode">
							<?php if ( $done ): ?>
								<a href="<?php echo get_edit_post_link( $migrated_posts_cache[ $post_id ] ) ?>" target="_blank">
									<?php echo get_the_title( $post_id ); ?>
								</a>
							<?php else: ?>
								<?php echo get_the_title( $post_id ); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<script type="text/javascript">
		jQuery(function($) {
			var posts_to_migrate = $("#posts_to_migrate tbody tr").length;

			function update_migration_progress_bar() {
				var posts_done = $("#posts_to_migrate tbody tr.done").length;
				progress = Math.round(posts_done / posts_to_migrate * 100)
				$("#migration_progress .bar")
					.css("width", progress + "%")
					.html(posts_done + " / " + posts_to_migrate);

				if ( progress == 100 ) {
					$("#migration_progress")
						.removeClass("active")
						.addClass("progress-success")
						.find(".bar").html("Done! Whoop whoop!");

					$("#migration-header small").html('');
					$("#start_migration_button").addClass("disabled");
					$("#continue_to_finish_button").removeClass("disabled");
				}
			};

			function podlove_migrate_one_post() {
				$("#posts_to_migrate tbody tr:not(.done):first").each(function() {
					var post_id = $(this).data("post-id")
					    that = $(this),
					    episode_title = $(".episode", that).html();

					$("#migration-header small").html(episode_title);

					var data = {
						action: 'podlove-migrate-post',
						post_id: post_id
					};

					$.ajax({
						url: ajaxurl,
						data: data,
						dataType: 'json',
						beforeSend: function(jqXHR, settings) {
							$(".waiting, .done", that).hide();
							$(".migrating", that).show();
						},
						success: function(result) {
							var episode_url = result.url;

							$(".waiting, .migrating", that).hide();
							$(".done", that).show();
							that.addClass("done");

							// add link
							$(".episode", that).html('<a href="' + episode_url + '" target="_blank">' + episode_title + '</a>')

							// update progress bar
							update_migration_progress_bar();

							// continue
							podlove_migrate_one_post();
						}
					});
				});
			}

			$("#start_migration_button").on("click", function(){
				if (!$(this).hasClass("disabled")) {
					$(this).addClass("disabled");
					podlove_migrate_one_post();
				}
			});
			update_migration_progress_bar();
		});
		</script>
		<?php
	}

}