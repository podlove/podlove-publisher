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
					<input type="hidden" name="step" value="4">
					<input type="hidden" name="page" value="podlove_settings_migration_handle">
					<input type="submit" class="btn btn-warning" value="<?php echo __( 'Save and Migrate', 'podlove' ) ?>">
				</form>

				<form action="" method="POST">

					<input type="hidden" name="step" value="3">
					<input type="hidden" name="page" value="podlove_settings_migration_handle">

					<input type="submit" class="btn btn-primary" value="<?php echo __( 'Save and Refresh', 'podlove' ) ?>">

					<div class="clearfix"></div>

					<?php if ( count( $errors ) ): ?>
						<h3>Errors</h3>
						<?php foreach ( $errors as $error ): ?>
							<div class="alert alert-error">
								<?php echo $error ?>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>

					<h3><?php echo __( 'File Types', 'podlove' ) ?></h3>
					<table class="table table-striped">
						<thead>
							<tr>
						 		<th>
						 			<input type="checkbox" checked="checked">
						 		</th>
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

					<?php 
					if ( ! $slug_type = $migration_settings['slug'] ) {
						$slug_type = 'file';
					}
					?>

					<h3><?php echo __( 'Slug', 'podlove' ); ?></h3>
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

					<h3><?php echo __( 'Episodes', 'podlove' ); ?></h3>
					<table class="table table-striped">
						<thead>
							<tr>
								<th>
									<input type="checkbox" checked="checked">
								</th>
								<th>#</th>
								<th>
									<?php echo __( 'Title', 'podlove' ) ?>
								</th>
								</th>
								<th>
									<?php echo __( 'File Slug', 'podlove' ) ?>
								</th>
								<th>
									<?php echo __( 'WordPress Slug', 'podlove' ) ?>
								</th>
								<th>
									<?php echo __( 'Number Slug', 'podlove' ) ?>
								</th>
								<th>
									<?php echo __( 'Duration', 'podlove' ) ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $episodes as $episode_post ): ?>
								<?php $post_data = new Legacy_Post_Parser( $episode_post->ID ); ?>
								<tr>
									<td>
										<input type="checkbox" <?php checked( isset( $migration_settings['episodes'][ $episode_post->ID ] ) ) ?> name="podlove_migration[episodes][<?php echo $episode_post->ID ?>]">
									</td>
									<td>
										<?php echo $episode_post->ID ?>
									</td>
									<td>
										<a href="<?php echo get_edit_post_link( $episode_post->ID ) ?>" target="_blank">
							 				<?php echo get_the_title( $episode_post->ID ) ?>
										</a>
									</td>
									<td>
										<?php echo Assistant::get_file_slug( $episode_post ) ?>
									</td>
									<td>
										<?php echo $episode_post->post_name ?>
									</td>
									<td>
										<?php echo Assistant::get_number_slug( $episode_post ) ?>
									</td>
									<td>
										<?php echo $post_data->get_duration() ?>
									</td>
								</tr>
								<?php foreach ( $file_types as $file_type ): ?>
									<?php if ( isset( $migration_settings['file_types'][ $file_type['file_type']->id ] ) ): ?>
										<tr>
											<td colspan="2"></td>
											<td colspan="5">
												<?php echo sprintf( "%s%s.%s",
													$migration_settings['podcast']['media_file_base_url'],
													Assistant::get_episode_slug( $episode_post, $slug_type ),
													$file_type['file_type']->extension
												 ); ?>
											</td>
										</tr>
									<?php endif; ?>
								<?php endforeach; ?>
							<?php endforeach; ?>
						</tbody>
					</table>

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