<?php

namespace Podlove\Modules\Migration\Settings\Wizard;

use Podlove\Modules\Migration\Enclosure;
use Podlove\Modules\Migration\Legacy_Post_Parser;
use Podlove\Modules\Migration\Settings\Assistant;

class StepPosts extends Step
{
    public $title = 'Episode Setup';

    public function template()
    {
        $args = ['posts_per_page' => -1];

        $episodes = [];
        $file_types = [];
        $errors = [];

        $query = new \WP_Query($args);
        while ($query->have_posts()) {
            $query->next_post();

            // look for the cover
            $metas = get_post_meta($query->post->ID, '', true);
            $metas = array_map(function ($m) {
                return $m[0];
            }, $metas);
            foreach ($metas as $key => $value) {
                if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                    continue;
                }

                $enclosure = Enclosure::from_url($value, $query->post->ID);

                if (!in_array($enclosure->extension, ['gif', 'jpg', 'jpeg', 'png'])) {
                    continue;
                }

                if ($enclosure->errors) {
                    $errors = array_merge($enclosure->errors, $errors);
                } elseif (isset($file_types[$enclosure->file_type->id])) {
                    ++$file_types[$enclosure->file_type->id]['count'];
                } else {
                    $file_types[$enclosure->file_type->id] = ['file_type' => $enclosure->file_type, 'count' => 1];
                }
            }

            $enclosures = Enclosure::all_for_post($query->post->ID);
            foreach ($enclosures as $enclosure) {
                if ($enclosure->errors) {
                    $errors = array_merge($enclosure->errors, $errors);
                } elseif (isset($file_types[$enclosure->file_type->id])) {
                    ++$file_types[$enclosure->file_type->id]['count'];
                } else {
                    $file_types[$enclosure->file_type->id] = ['file_type' => $enclosure->file_type, 'count' => 1];
                }
            }

            if (count($enclosures)) {
                foreach ($enclosures as $enclosure) {
                    if (empty($enclosure->errors)) {
                        $episodes[] = $query->post;

                        break;
                    }
                }
            }
        }

        if (!count($episodes)) {
            $errors[] = __('<strong>No Episodes Found!</strong> You can only migrate posts with enclosures. However, there don\'t seem to be any. Sorry!', 'podlove-podcasting-plugin-for-wordpress');
        }

        $migration_settings = get_option('podlove_migration', []);

        // educated guess: activate all audio files by default
        if (!isset($migration_settings['file_types'])) {
            $migration_settings['file_types'] = [];
            foreach ($file_types as $file_type) {
                if (stripos($file_type['file_type']->mime_type, 'audio') !== false) {
                    $migration_settings['file_types'][$file_type['file_type']->id] = 'on';
                }
            }
        }

        $slug_type = 'file';
        if (!isset($migration_settings['post_slug']) || !$post_slug_type = $migration_settings['post_slug']) {
            $post_slug_type = 'wordpress';
        }

        if (!isset($migration_settings['cleanup'])) {
            $migration_settings['cleanup'] = [];
        }
        $migration_settings['cleanup'] = wp_parse_args($migration_settings['cleanup'], [
            'enclosures' => 1,
            'player' => 1,
            'template' => 'bottom',
        ]);

        $validation_cache = get_option('podlove_migration_validation_cache', []); ?>

		<div class="row-fluid">
			<div class="span12">
				<form action="" method="POST" class="pull-left" style="margin-right: 15px">
					<input type="hidden" name="page" value="podlove_settings_migration_handle">
					<input type="submit" name="prev" class="btn" value="<?php echo __('Back', 'podlove-podcasting-plugin-for-wordpress'); ?>">
					<input type="submit" name="next" class="btn btn-primary pull-right" value="<?php echo __('Continue to Migration', 'podlove-podcasting-plugin-for-wordpress'); ?>">

					<div class="clearfix"></div>

					<div class="row-fluid">
						<div class="span12">
							<h3>About Episodes and Assets</h3>
							<p>
								A single podcast recording is called <em>episode</em>. Below are all posts identified as episodes. Please check that the results match your expectations.
								Podlove knows about the <em>assets</em> you distribute for each episode. Assets are audio/video files but also images, chapter files, subtitle files etc.
							</p>
							<p>
								When everything looks good and the verification bar is green (and maybe yellow, but not red), <em>continue to migration</em>.
							</p>
						</div>
					</div>

					<div class="clearfix"></div>

					<?php if (count($errors)) { ?>
						<h3>Warnings</h3>
						<?php foreach ($errors as $error) { ?>
							<div class="alert">
								<?php echo $error; ?>
							</div>
						<?php } ?>
					<?php } ?>

					<h3><?php echo __('Episode Assets', 'podlove-podcasting-plugin-for-wordpress'); ?> <small>choose assets to use in your podcast</small></h3>
					<p>
						These are all detected assets. Please choose those that you want to use in your podcast.
						For each asset, a feed will be created and users will be able to download those files (you can change this after the migration).
					</p>
					<table class="table table-striped">
						<thead>
							<tr>
						 		<th>
						 			<?php echo __('Use', 'podlove-podcasting-plugin-for-wordpress'); ?>
						 		</th>
						 		<th>
						 			<?php echo __('Title', 'podlove-podcasting-plugin-for-wordpress'); ?>
						 		</th>
						 		<th>
						 			<?php echo __('Mime Type', 'podlove-podcasting-plugin-for-wordpress'); ?>
						 		</th>
						 		<th>
						 			<?php echo __('Extension', 'podlove-podcasting-plugin-for-wordpress'); ?>
						 		</th>
						 		<th>
						 			<?php echo __('Matching Episodes', 'podlove-podcasting-plugin-for-wordpress'); ?>
						 		</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($file_types as $file_type) { ?>
								<tr>
									<td>
										<input type="checkbox"
											data-filetype-id="<?php echo $file_type['file_type']->id; ?>"
											<?php checked(isset($migration_settings['file_types'][$file_type['file_type']->id]) && $migration_settings['file_types'][$file_type['file_type']->id] == 'on'); ?>
											name="podlove_migration[file_types][<?php echo $file_type['file_type']->id; ?>]"
											class="asset_checkbox"
										>
									</td>
									<td>
										<?php echo $file_type['file_type']->name; ?>
									</td>
									<td>
										<?php echo $file_type['file_type']->mime_type; ?>
									</td>
									<td>
										<?php echo $file_type['file_type']->extension; ?>
									</td>
									<td>
										<?php echo $file_type['count']; ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>

					<input type="hidden" name="podlove_migration[slug]" value="file"/>

					<div class="row-fluid">

						<div class="span6" id="post_slug_select">
							<h3><?php echo __('WordPress Post Slug', 'podlove-podcasting-plugin-for-wordpress'); ?> <small>Url slug for the WordPress page</small></h3>
							<label class="radio">
								<input type="radio" name="podlove_migration[post_slug]" value="wordpress" <?php checked($post_slug_type == 'wordpress'); ?>>
								Reuse previous WordPress post slug.
							</label>
							<label class="radio">
								<input type="radio" name="podlove_migration[post_slug]" value="file" <?php checked($post_slug_type == 'file'); ?>>
								Extract slug from file name. Use file basename.
							</label>
							<label class="radio">
								<input type="radio" name="podlove_migration[post_slug]" value="number" <?php checked($post_slug_type == 'number'); ?>>
								Number Slug: This is the number of your episode with leading zeros.
							</label>
						</div>

						<div class="span6">
							<h3><?php echo __('Clean up migrated Episodes', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>

							<div class="form-horizontal" id="cleanup_settings">

								<div class="control-group enclosures">
									<label class="control-label" data-toggle="tooltip" title="You probably want to remove all enclosures stored in custom post meta. The Podlove Publisher imports and stores them separately.">
										Enclosures
									</label>
									<div class="controls">
										<label class="radio">
											<input type="radio" name="podlove_migration[cleanup][enclosures]" value="1" <?php checked($migration_settings['cleanup']['enclosures'], 1); ?>>
											remove all enclosure custom fields
										</label>
										<label class="radio">
											<input type="radio" name="podlove_migration[cleanup][enclosures]" value="0" <?php checked($migration_settings['cleanup']['enclosures'], 0); ?>>
											keep all enclosures custom fields
										</label>
									</div>
								</div>

								<div class="control-group player">
									<label class="control-label" data-toggle="tooltip" title="You probably want to get rid of your old players.">
										Web Player Shortcodes
									</label>
									<div class="controls">
										<label class="radio">
											<input type="radio" name="podlove_migration[cleanup][player]" value="1" <?php checked($migration_settings['cleanup']['player'], 1); ?>>
											remove all player codes
										</label>
										<label class="radio">
											<input type="radio" name="podlove_migration[cleanup][player]" value="0" <?php checked($migration_settings['cleanup']['player'], 0); ?>>
											keep all player codes
										</label>
									</div>
								</div>

								<div class="control-group template">
									<label class="control-label">Add template containing web player and download buttons</label>
									<div class="controls">
										<label class="radio">
											<input type="radio" name="podlove_migration[cleanup][template]" value="bottom" <?php checked($migration_settings['cleanup']['template'], 'bottom'); ?>>
											insert at the end of the content
										</label>
										<label class="radio">
											<input type="radio" name="podlove_migration[cleanup][template]" value="top" <?php checked($migration_settings['cleanup']['template'], 'top'); ?>>
											insert at the beginning of the content
										</label>
										<label class="radio">
											<input type="radio" name="podlove_migration[cleanup][template]" value="manually" <?php checked($migration_settings['cleanup']['template'], 'manually'); ?>>
											don't insert automatically
										</label>
									</div>
								</div>

							</div>
						</div>
						
					</div>

					<h3><?php echo __('Episode Verification Status', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>
					<div class="progress progress-striped" id="verification-status">
					  <div class="bar bar-success" style="width:0%;display:none" data-toggle="tooltip" title="All media files for these episodes are valid."></div>
					  <div class="bar bar-warning" style="width:0%;display:none" data-toggle="tooltip" title="Some, but not all media files for these episodes are missing. You can add them later, so not necessarily a dealbreaker."></div>
					  <div class="bar bar-danger" style="width:0%;display:none" data-toggle="tooltip" title="No media files for these episodes exist!"></div>
					</div>

					<ul>
						<li>
							<span style="color: #00CE62">Green:</span> All media files for these episodes are valid.
						</li>
						<li>
							<span style="color: #FDB339">Yellow:</span> Some, but not all media files for these episodes are missing. You can add them later, so not necessarily a dealbreaker.
						</li>
						<li>
							<span style="color: #FF484C">Red:</span> No media for these episodes exist!
						</li>
					</ul>

					<h3><?php echo __('Episodes', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>
					<p>
						Select the episodes to migrate. Use the rest of the displayed data to verify the extracted data.
					</p>
					<table class="table table-striped">
						<thead>
							<tr>
								<th>
									<input type="checkbox" checked="checked" id="toggle_all_episodes">
								</th>
								<th>#</th>
								<th>
									<?php echo __('Detected Episode', 'podlove-podcasting-plugin-for-wordpress'); ?>
								</th>
							</tr>
						</thead>
						<tbody id="episodes_to_migrate">
							<?php foreach ($episodes as $episode_post) { ?>
								<?php $post_data = new Legacy_Post_Parser($episode_post->ID); ?>
								<tr>
									<td>
										<input type="checkbox" class="episode_checkbox" <?php checked(isset($migration_settings['episodes'][$episode_post->ID]) || !isset($migration_settings['episodes'])); ?> name="podlove_migration[episodes][<?php echo $episode_post->ID; ?>]">
									</td>
									<td>
										<?php echo $episode_post->ID; ?>
									</td>
									<td>
										<a href="<?php echo get_edit_post_link($episode_post->ID); ?>" target="_blank">
							 				<?php echo get_the_title($episode_post->ID); ?>
										</a>
									</td>
								</tr>
								<tr>
									<td colspan="2"></td>
									<td>
										<table class="table table-condensed table-bordered">
											<tr>
												<th>Post Slug</th>
												<td class="slug">
													<?php
                                                    $slugs = [
                                                        'wordpress' => $episode_post->post_name,
                                                        'file' => Assistant::get_file_slug($episode_post),
                                                        'number' => Assistant::get_number_slug($episode_post),
                                                    ];

        foreach ($slugs as $slug_key => $slug_value) {
            $class = $post_slug_type == $slug_key ? '' : 'hidden';
            echo "<span class='{$slug_key} {$class}'>".$slug_value.'</span>';
        } ?>
												</td>
											</tr>
											<tr>
												<th>Subtitle</th>
												<td><?php echo $post_data->get_subtitle(); ?></td>
											</tr>
											<tr>
												<th>Summary</th>
												<td><?php echo $post_data->get_summary(); ?></td>
											</tr>
											<tr>
												<th>Duration</th>
												<td><?php echo $post_data->get_duration(); ?></td>
											</tr>
											<tr>
												<th>Episode Media File Slug</th>
												<td><?php echo Assistant::get_file_slug($episode_post); ?></td>
											</tr>
											<tr>
												<th>Assets</th>
												<td>
													<table class="table table-condensed table-bordered migration_assets" style="margin-bottom:0">
														<?php foreach ($file_types as $file_type) { ?>
															<?php
                                                            $is_asset_active = isset($migration_settings['file_types'][$file_type['file_type']->id]);
        $asset_name = $file_type['file_type']->name;
        $asset_url = sprintf(
            '%s%s.%s',
            \Podlove\Modules\Migration\get_media_file_base_url(),
            Assistant::get_episode_slug($episode_post, $slug_type),
            $file_type['file_type']->extension
        );

        $status = 'unknown';
        if (isset($validation_cache[$asset_url])) {
            $status = $validation_cache[$asset_url] ? 'success' : 'failure';
        } ?>
															<tr data-filetype-id="<?php echo $file_type['file_type']->id; ?>" <?php echo (!$is_asset_active) ? 'class="hidden"' : ''; ?>>
																<td>
																	<?php echo $asset_name; ?>
																</td>
																<td>
																	<?php
                                                                    echo sprintf(
            '<a href="%s" target="_blank">%s</a>',
            $asset_url,
            Assistant::get_episode_slug($episode_post, $slug_type).'.'.$file_type['file_type']->extension
        ); ?>
																	<div class="update pull-right">
																		<div style="display: none">
																			updating ...
																		</div>
																		<div>
																			<button class="button verify_migration_asset <?php echo ($status != 'unknown') ? 'visited' : ''; ?>">verify</button>
																		</div>
																	</div>
																	<div class="status pull-right">
																		<div class="success" <?php echo ($status == 'success') ? '' : 'style="display: none"'; ?>>
																			<i class="podlove-icon-ok"></i>
																		</div>
																		<div class="failure" <?php echo ($status == 'failure') ? '' : 'style="display: none"'; ?>>
																			<i class="podlove-icon-remove"></i>
																		</div>
																	</div>
																</td>
															</tr>
														<?php } ?>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>

					<style type="text/css">
					.migration_assets .update, .migration_assets .status {
						margin-left: 10px;
					}
					</style>

					<script type="text/javascript">
					jQuery(function($) {
						var episodes_to_check = $(".migration_assets").length;

						$("#toggle_all_episodes").on("click", function() {
							var checked = $(this).attr("checked") == "checked";
							$("#episodes_to_migrate input[type='checkbox']").attr("checked", checked);
							podlove_migration_update_progress_bar();
						});

						$(".verify_migration_asset").on("click", function(e) {
							e.preventDefault();
							podlove_validate_one_asset($(this), false);
							return false;
						});

						$(".episode_checkbox").on("click", function() {
							podlove_migration_update_progress_bar();
						});

						$(".asset_checkbox").on("click", function(){
							var checked = $(this).prop("checked"),
							    filetype_id = $(this).data("filetype-id");

							if (checked) {
								$('.migration_assets tr[data-filetype-id="' + filetype_id + '"]').removeClass("hidden");
							} else {
								$('.migration_assets tr[data-filetype-id="' + filetype_id + '"]').addClass("hidden");
							}

							$.ajax({
								url: ajaxurl,
								data: {
									action: 'podlove-update-migration-settings',
									file_types: [ filetype_id, checked ]
								},
								dataType: 'json'
							});

							podlove_migration_update_progress_bar();
						});

						$("#post_slug_select input").on("click", function(){
							var slug_type = $(this).val();

							$(".slug span." + slug_type).removeClass("hidden");
							$(".slug span:not(." + slug_type + ")").addClass("hidden");

							$.ajax({
								url: ajaxurl,
								data: {
									action: 'podlove-update-migration-settings',
									post_slug: slug_type
								},
								dataType: 'json'
							});
						});

						$("#cleanup_settings .enclosures input").on("click", function(){
							$.ajax({
								url: ajaxurl,
								data: {
									action: 'podlove-update-migration-settings',
									cleanup: [ 'enclosures', $(this).val() ]
								},
								dataType: 'json'
							});
						});

						$("#cleanup_settings .player input").on("click", function(){
							$.ajax({
								url: ajaxurl,
								data: {
									action: 'podlove-update-migration-settings',
									cleanup: [ 'player', $(this).val() ]
								},
								dataType: 'json'
							});
						});

						$("#cleanup_settings .template input").on("click", function(){
							$.ajax({
								url: ajaxurl,
								data: {
									action: 'podlove-update-migration-settings',
									cleanup: [ 'template', $(this).val() ]
								},
								dataType: 'json'
							});
						});

						function podlove_validate_one_asset(button, continue_validation) {

							var container = button.closest("tr"),
							    file_url  = container.find("a").attr("href");

							if (!file_url) 
								return;

							var data = {
								action: 'podlove-validate-url',
								file_url: file_url
							};

							// toggle status
							container.find('.update div').toggle();

							// mark button as once clicked
							button.addClass("visited");

							$.ajax({
								url: ajaxurl,
								data: data,
								dataType: 'json',
								success: function(result) {
									if ( result.file_size > 0) {
										$('.success', container).show();
										$('.failure', container).hide();
									} else {
										$('.success', container).hide();
										$('.failure', container).show();
									}
									container.find('.update div').toggle();
									podlove_migration_update_progress_bar();

									if (continue_validation) {
										podlove_validate_one_asset($(".verify_migration_asset:not(.visited):first"), true);
									}
								}
							});
						}

						$(document).ready(function(){
							podlove_validate_one_asset($(".verify_migration_asset:not(.visited):first"), true);
						});

						function podlove_migration_update_progress_bar() {

							var all_valid = 0,
							    some_valid = 0,
							    none_valid = 0;

							$('.episode_checkbox').filter(":checked").closest("tr").next().find(".migration_assets")
								.each(function(){
								var visible_tr = $("tr:not(.hidden)", this),
									successes  = $(".success", visible_tr).filter(":visible").length,
								    failures   = $(".failure", visible_tr).filter(":visible").length;

								if (successes + failures === $(".status", visible_tr).length) {
									if (successes > 0 && failures > 0) {
										some_valid++;
									} else if (failures > 0) {
										none_valid++;
									} else if (successes > 0) {
										all_valid++;
									}
								}
							});

							console.log(all_valid,some_valid,none_valid);

							var success_percent = Math.round(all_valid / episodes_to_check * 10000) / 100,
							    warning_percent = Math.round(some_valid / episodes_to_check * 10000) / 100,
							    failed_percent = Math.round(none_valid / episodes_to_check * 10000) / 100;

							if (success_percent + warning_percent + failed_percent > 100) {
								success_percent -= 0.01;
								warning_percent -= 0.01;
								failed_percent  -= 0.01;
							}

							if (success_percent > 0) {
							    $("#verification-status .bar-success")
							    	.css("width", success_percent + "%")
							    	.html(all_valid)
							    	.show();
							} else {
								$("#verification-status .bar-success").hide();
							}

							if (warning_percent > 0) {
						    	$("#verification-status .bar-warning")
							    	.css("width", warning_percent + "%")
							    	.html(some_valid)
							    	.show();
							} else {
								$("#verification-status .bar-warning").hide();
							}

							if (failed_percent > 0) {
						    	$("#verification-status .bar-danger")
							    	.css("width", failed_percent + "%")
							    	.html(none_valid)
							    	.show();
							} else {
								$("#verification-status .bar-danger").hide();
							}

							if (!success_percent && !warning_percent && !failed_percent) {
						    	$("#verification-status .bar-danger")
							    	.css("width", "100%").html(episodes_to_check).show();
							}
						};
						
						podlove_migration_update_progress_bar();
					});
					</script>

					<input type="submit" name="prev" class="btn" value="<?php echo __('Back', 'podlove-podcasting-plugin-for-wordpress'); ?>">
					<input type="submit" name="next" class="btn btn-primary pull-right" value="<?php echo __('Continue to Migration', 'podlove-podcasting-plugin-for-wordpress'); ?>">
					
				</form>
			</div>
		</div>
		<?php

        // Restore original Query & Post Data
        wp_reset_query();
        wp_reset_postdata();
    }
}
