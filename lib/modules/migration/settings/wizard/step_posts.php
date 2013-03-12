<?php
namespace Podlove\Modules\Migration\Settings\Wizard;
use Podlove\Modules\Migration\Enclosure;
use Podlove\Modules\Migration\Legacy_Post_Parser;
use Podlove\Modules\Migration\Settings\Assistant;

class StepPosts extends Step {

	public $title = 'Post Selection';
	
	public function template() {

		$args = array(
			// 'meta_key'       => 'enclosure',
			'posts_per_page' => -1
		);

		$episodes   = array();
		$file_types = array();
		$errors     = array();

		$query = new \WP_Query( $args );
		while( $query->have_posts() ) {
			$query->next_post();
			
			// look for the cover
			$metas = get_post_meta( $query->post->ID, '', true );
			$metas = array_map( function($m) { return $m[0]; }, $metas );
			foreach ( $metas as $key => $value ) {

				if ( filter_var( $value, FILTER_VALIDATE_URL ) === false  )
					continue;

				$enclosure = Enclosure::from_url( $value, $query->post->ID );

				if ( ! in_array( $enclosure->extension, array( 'gif', 'jpg', 'jpeg', 'png' ) ) )
					continue;

				if ( $enclosure->errors ) {
					$errors = array_merge( $enclosure->errors, $errors );
				} elseif ( isset( $file_types[ $enclosure->file_type->id ] ) ) {
					$file_types[ $enclosure->file_type->id ]['count'] += 1;
				} else {
					$file_types[ $enclosure->file_type->id ] = array( 'file_type' => $enclosure->file_type, 'count' => 1 );
				}
			}

			$enclosures = Enclosure::all_for_post( $query->post->ID );
			foreach ( $enclosures as $enclosure ) {
				if ( $enclosure->errors ) {
					$errors = array_merge( $enclosure->errors, $errors );
				} elseif ( isset( $file_types[ $enclosure->file_type->id ] ) ) {
					$file_types[ $enclosure->file_type->id ]['count'] += 1;
				} else {
					$file_types[ $enclosure->file_type->id ] = array( 'file_type' => $enclosure->file_type, 'count' => 1 );
				}
			}

			if ( count( $enclosures ) )
				$episodes[] = $query->post;
			
		}

		if ( ! count( $episodes ) )
			$errors[] = __( '<strong>No Episodes Found!</strong> I only know how to migrate episodes with enclosures. However, I could\'t find any. Sorry!' , 'podlove' );

		$migration_settings = get_option( 'podlove_migration', array() );

		// educated guess: activate all audio files by default
		if ( ! isset( $migration_settings['file_types'] ) ) {
			$migration_settings['file_types'] = array();
			foreach ( $file_types as $file_type ) {
				if ( stripos( $file_type['file_type']->mime_type, 'audio' ) !== false ) {
					$migration_settings['file_types'][ $file_type['file_type']->id  ] = 'on';
				}
			}
		}
		?>

		<div class="row-fluid">
			<div class="span12">
				<div class="well">
					I searched your posts for entries with enclosures.
					I also looked into them for their mime type.
					<br>
					Please select which files and which episodes you'd like to migrate.
				</div>
			</div>
		</div>

		<div class="row-fluid">
			<div class="span12">
				<form action="" method="POST" class="pull-left" style="margin-right: 15px">
					<input type="hidden" name="page" value="podlove_settings_migration_handle">
					<input type="submit" name="next" class="btn btn-warning" value="<?php echo __( 'Save and Migrate', 'podlove' ) ?>">
					<input type="submit" name="stay" class="btn btn-primary" value="<?php echo __( 'Save and Refresh', 'podlove' ) ?>">

					<div class="clearfix"></div>

					<?php if ( count( $errors ) ): ?>
						<h3>Errors</h3>
						<?php foreach ( $errors as $error ): ?>
							<div class="alert alert-error">
								<?php echo $error ?>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>

					<h3><?php echo __( 'Episode Assets', 'podlove' ) ?></h3>
					<table class="table table-striped">
						<thead>
							<tr>
						 		<th></th>
						 		<th>
						 			<?php echo __( 'Title', 'podlove' ) ?>
						 		</th>
						 		<th>
						 			<?php echo __( 'Mime Type', 'podlove' ) ?>
						 		</th>
						 		<th>
						 			<?php echo __( 'Extension', 'podlove' ) ?>
						 		</th>
						 		<th>
						 			<?php echo __( 'Matching Episodes', 'podlove' ) ?>
						 		</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $file_types as $file_type ): ?>
								<tr>
									<td>
										<input type="checkbox" <?php checked( isset( $migration_settings['file_types'][ $file_type['file_type']->id ] ) ) ?> name="podlove_migration[file_types][<?php echo $file_type['file_type']->id ?>]">
									</td>
									<td>
										<?php echo $file_type['file_type']->name ?>
									</td>
									<td>
										<?php echo $file_type['file_type']->mime_type ?>
									</td>
									<td>
										<?php echo $file_type['file_type']->extension ?>
									</td>
									<td>
										<?php echo $file_type['count'] ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<?php $slug_type = 'file'; ?>
					<input type="hidden" name="podlove_migration[slug]" value="file"/>

					<?php /*
					<?php 
					if ( ! $slug_type = $migration_settings['slug'] ) {
						$slug_type = 'file';
					}
					?>
					<h3><?php echo __( 'Episode Media File Slug', 'podlove' ); ?></h3>
					<label class="radio">
						<input type="radio" name="podlove_migration[slug]" value="file" <?php checked( $slug_type == 'file' ) ?>>
						File Slug
					</label>
					<label class="radio">
						<input type="radio" name="podlove_migration[slug]" value="wordpress" <?php checked( $slug_type == 'wordpress' ) ?>>
						WordPress Slug
					</label>
					<label class="radio">
						<input type="radio" name="podlove_migration[slug]" value="number" <?php checked( $slug_type == 'number' ) ?>>
						Number Slug
					</label>
					*/
					?>

					<?php 
					if ( ! isset( $migration_settings['post_slug'] ) || ! $post_slug_type = $migration_settings['post_slug'] ) {
						$post_slug_type = 'wordpress';
					}
					?>

					<h3><?php echo __( 'Post Slug', 'podlove' ); ?></h3>
					<label class="radio">
						<input type="radio" name="podlove_migration[post_slug]" value="wordpress" <?php checked( $post_slug_type == 'wordpress' ) ?>>
						Reuse previous WordPress post slug.
					</label>
					<label class="radio">
						<input type="radio" name="podlove_migration[post_slug]" value="file" <?php checked( $post_slug_type == 'file' ) ?>>
						Extract slug from file name. Use file basename.
					</label>
					<label class="radio">
						<input type="radio" name="podlove_migration[post_slug]" value="number" <?php checked( $post_slug_type == 'number' ) ?>>
						Number Slug: This is the number of your episode with leading zeros.
					</label>

					<h3><?php echo __( 'Episodes', 'podlove' ); ?></h3>
					<table class="table table-striped">
						<thead>
							<tr>
								<th>
									<input type="checkbox" checked="checked" id="toggle_all_episodes">
								</th>
								<th>#</th>
								<th>
									<?php echo __( 'Detected Episode', 'podlove' ) ?>
								</th>
							</tr>
						</thead>
						<tbody id="episodes_to_migrate">
							<?php foreach ( $episodes as $episode_post ): ?>
								<?php $post_data = new Legacy_Post_Parser( $episode_post->ID ); ?>
								<tr>
									<td>
										<input type="checkbox" <?php checked( isset( $migration_settings['episodes'][ $episode_post->ID ] ) || ! isset( $migration_settings['episodes'] ) ) ?> name="podlove_migration[episodes][<?php echo $episode_post->ID ?>]">
									</td>
									<td>
										<?php echo $episode_post->ID ?>
									</td>
									<td>
										<a href="<?php echo get_edit_post_link( $episode_post->ID ) ?>" target="_blank">
							 				<?php echo get_the_title( $episode_post->ID ) ?>
										</a>
									</td>
								</tr>
								<tr>
									<td colspan="2"></td>
									<td>
										<table class="table table-condensed table-bordered">
											<tr>
												<th>Post Slug</th>
												<td>
													<?php 
													switch ( $post_slug_type ) {
														case 'wordpress':
															echo $episode_post->post_name;
															break;
														case 'file':
															echo Assistant::get_file_slug( $episode_post );
															break;
														case 'number':
															echo Assistant::get_number_slug( $episode_post );
															break;
													}
													?>
												</td>
											</tr>
											<tr>
												<th>Subtitle</th>
												<td><?php echo $post_data->get_subtitle() ?></td>
											</tr>
											<tr>
												<th>Summary</th>
												<td><?php echo $post_data->get_summary() ?></td>
											</tr>
											<tr>
												<th>Duration</th>
												<td><?php echo $post_data->get_duration() ?></td>
											</tr>
											<tr>
												<th>Episode Media File Slug</th>
												<td><?php echo Assistant::get_file_slug( $episode_post ) ?></td>
											</tr>
											<tr>
												<th>Assets</th>
												<td>
													<table class="table table-condensed table-bordered">
														<?php foreach ( $file_types as $file_type ): ?>
														<tr>
															<td>
																<?php if ( isset( $migration_settings['file_types'][ $file_type['file_type']->id ] ) ): ?>
																	<?php
																	$asset_name = $file_type['file_type']->name;
																	$asset_url = sprintf( "%s%s.%s",
																		\Podlove\Modules\Migration\get_media_file_base_url(),
																		Assistant::get_episode_slug( $episode_post, $slug_type ),
																		$file_type['file_type']->extension
																	 );

																	echo $asset_name;
																	?>
																<?php endif; ?>
															</td>
															<td>
																<?php 
																echo sprintf(
																	'<a href="%s" target="_blank">%s</a>',
																	$asset_url,
																	Assistant::get_episode_slug( $episode_post, $slug_type ) . '.' . $file_type['file_type']->extension
																);
																?>
															</td>
															<td class="status">
																<div class="success" style="display: none">
																	<span style="color: green">✓</span>
																</div>
																<div class="failure" style="display: none">
																	<span style="color: red">!!!</span>
																</div>
															</td>
															<td class="update">
																<div style="display: none">
																	updating ...
																</div>
																<div>
																	<button class="button verify_migration_asset">verify</button>
																</div>
															</td>
														</tr>
														<?php endforeach; ?>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<script type="text/javascript">
					jQuery(function($) {

						$("#toggle_all_episodes").on("click", function() {
							var checked = $(this).attr("checked") == "checked";
							$("#episodes_to_migrate input[type='checkbox']").attr("checked", checked);
						});

						$(".verify_migration_asset").on("click", function(e) {
							e.preventDefault();

							var container = $(this).closest("tr");

							var data = {
								action: 'podlove-validate-url',
								file_url: container.find("a").attr("href")
							};

							container.find('.update div').toggle();

							var request = $.ajax({
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
								}
							});

							return false;
						});

						$(document).ready(function(){
							$(".verify_migration_asset").click();
						});
						
					});
					</script>

					<input type="submit" class="btn btn-primary" value="<?php echo __( 'Save and Continue', 'podlove' ) ?>">
					
				</form>
			</div>
		</div>
		<?php

		// Restore original Query & Post Data
		wp_reset_query();
		wp_reset_postdata();
	}

}