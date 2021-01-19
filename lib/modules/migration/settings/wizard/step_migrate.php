<?php

namespace Podlove\Modules\Migration\Settings\Wizard;

use Podlove\Model;
use Podlove\Modules\Migration;

class StepMigrate extends Step
{
    public $title = 'Migrate';

    public function template()
    {
        // load already migrated posts
        $migrated_posts_cache = get_option('podlove_migrated_posts_cache', []);

        // then begin to migrate
        $migration_settings = get_option('podlove_migration', []);

        // Basic Podcast Settings
        $podcast = Model\Podcast::get();
        $podcast->title = $migration_settings['podcast']['title'];
        $podcast->subtitle = $migration_settings['podcast']['subtitle'];
        $podcast->summary = $migration_settings['podcast']['summary'];
        $podcast->media_file_base_uri = \Podlove\Modules\Migration\get_media_file_base_url();

        // harvest low hanging podPress fruits
        if ($podPress_config = get_option('podPress_config')) {
            if (isset($podPress_config['iTunes']['image']) && !$podcast->cover_image) {
                $podcast->cover_image = $podPress_config['iTunes']['image'];
            }
        }

        // harvest low hanging PowerPress fruits
        if ($powerPress_config = get_option('powerpress_feed')) {
            if (isset($powerPress_config['itunes_image']) && !$podcast->cover_image) {
                $podcast->cover_image = $powerPress_config['itunes_image'];
            }
            if (isset($powerPress_config['itunes_cat_1']) && !$podcast->category_1) {
                $podcast->category_1 = $powerPress_config['itunes_cat_1'];
            }
            if (isset($powerPress_config['itunes_cat_2']) && !$podcast->category_2) {
                $podcast->category_2 = $powerPress_config['itunes_cat_2'];
            }
            if (isset($powerPress_config['itunes_cat_3']) && !$podcast->category_3) {
                $podcast->category_3 = $powerPress_config['itunes_cat_3'];
            }
        }

        $podcast->save();

        // Create Template
        $template = Model\Template::find_one_by_title('default');
        if (!$template) {
            $template = new Model\Template();
            $template->title = 'default';
            $template->content = <<<'EOT'
{{ episode.player }}
[podlove-episode-downloads]
EOT;
            $template->save();
        }

        $template_assignment = Model\TemplateAssignment::get_instance();
        if ($template_assignment->has_property($migration_settings['cleanup']['template'])) {
            $template_assignment->{$migration_settings['cleanup']['template']} = $template->id;
            $template_assignment->save();
        }

        // Create Assets
        $assets = [];
        foreach ($migration_settings['file_types'] as $file_type_id => $_) {
            $file_type = Model\FileType::find_one_by_id($file_type_id);
            $is_image = in_array($file_type->extension, ['png', 'jpg', 'jpeg', 'gif']);

            $asset = Model\EpisodeAsset::find_one_by_file_type_id($file_type_id);
            if (!$asset) {
                $asset = new Model\EpisodeAsset();
                $asset->title = $file_type->name;
                $asset->file_type_id = $file_type_id;
                $asset->downloadable = !$is_image;
                $asset->save();
            }
            $assets[] = $asset;

            if ($is_image) {
                $asset_assignments = get_option('podlove_asset_assignment', []);
                if (!$asset_assignments['image']) {
                    $asset_assignments['image'] = $asset->id;
                    update_option('podlove_asset_assignment', $asset_assignments);
                }
            }

            // create feeds
            if (stripos($file_type->mime_type, 'audio') !== false) {
                $feed = Model\Feed::find_one_by_episode_asset_id($asset->id);
                if (!$feed) {
                    $feed = new Model\Feed();
                    $feed->episode_asset_id = $asset->id;
                    $feed->name = $file_type->name;
                    $feed->title = $file_type->name;
                    $feed->slug = $file_type->extension;
                    $feed->format = 'rss';
                    $feed->enable = true;
                    $feed->discoverable = true;
                    $feed->limit_items = -1;
                    $feed->save();
                }
            }

            // set web player settings
            $webplayer_formats = get_option('podlove_webplayer_formats', []);
            if (!isset($webplayer_formats['audio'])) {
                $webplayer_formats['audio'] = [];
            }

            if (stripos($file_type->mime_type, 'audio/mpeg') !== false) {
                $webplayer_formats['audio']['mp3'] = $asset->id;
            } elseif (stripos($file_type->mime_type, 'audio/mp4') !== false) {
                $webplayer_formats['audio']['mp4'] = $asset->id;
            } elseif (stripos($file_type->mime_type, 'audio/ogg') !== false) {
                $webplayer_formats['audio']['ogg'] = $asset->id;
            } elseif (stripos($file_type->mime_type, 'audio/opus') !== false) {
                $webplayer_formats['audio']['opus'] = $asset->id;
            }
            update_option('podlove_webplayer_formats', $webplayer_formats);
        }

        // flush rules after migration
        set_transient('podlove_needs_to_flush_rewrite_rules', true); ?>
		
		<form action="" method="POST">
			<input type="submit" name="prev" class="btn" value="<?php echo __('Back', 'podlove-podcasting-plugin-for-wordpress'); ?>">
			<input type="submit" name="next" id="continue_to_finish_button" class="btn btn-primary disabled pull-right" value="<?php echo __('Continue to last step', 'podlove-podcasting-plugin-for-wordpress'); ?>">
		</form>

		<div class="row-fluid">
			<div class="span12">
				<h3 id="migration-header">Migrating <small></small></h3>
			</div>
		</div>

		<div class="progress progress-striped active" id="migration_progress">
			<div class="bar" style="width:0%"></div>
		</div>

		<p>
			<input type="button" id="start_migration_button" class="btn btn-primary" value="<?php echo __('Start Migration', 'podlove-podcasting-plugin-for-wordpress'); ?>">
			Starting the migration creates the actual episodes one by one.			
		</p>

		<table class="table table-condensed" id="posts_to_migrate">
			<thead>
				<tr>
					<th>Status</th>
					<th>Episode</th>
				</tr>
			</thead>
			<tbody>
				<?php $migrated_post_ids = array_keys($migrated_posts_cache); ?>
				<?php foreach ($migration_settings['episodes'] as $post_id => $_) { ?>
					<?php $done = in_array($post_id, $migrated_post_ids); ?>
					<tr data-post-id="<?php echo $post_id; ?>" <?php echo ($done) ? 'class="done"' : ''; ?>>
						<td class="status">
							<span class="waiting" <?php echo (!$done) ? '' : 'style="display:none"'; ?>>not yet migrated</span>
							<span class="migrating" style="display:none"><i class="podlove-icon-spinner rotate"></i></span>
							<span class="done" <?php echo ($done) ? '' : 'style="display:none"'; ?>><i class="podlove-icon-ok"></i></span>
						</td>
						<td class="episode">
							<?php if ($done) { ?>
								<a href="<?php echo get_edit_post_link($migrated_posts_cache[$post_id]); ?>" target="_blank">
									<?php echo get_the_title($post_id); ?>
								</a>
							<?php } else { ?>
								<?php echo get_the_title($post_id); ?>
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>

		<script type="text/javascript">
		jQuery(function($) {
			var posts_to_migrate = $("#posts_to_migrate tbody tr").length;

			$("#continue_to_finish_button").hide();

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
					$("#continue_to_finish_button").removeClass("disabled").show();
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
