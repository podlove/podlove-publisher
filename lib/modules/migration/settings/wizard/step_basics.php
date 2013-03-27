<?php
namespace Podlove\Modules\Migration\Settings\Wizard;
use Podlove\Modules\Migration\Enclosure;

class StepBasics extends Step {

	public $title = 'Basic Settings';
	
	public function template() {
		?>
		<div class="row-fluid">
			<div class="span6">
				<h3>The Podlove Mindset</h3>
				<p>
					The Publisher assumes all your media files are in the same directory and follow
					a certain naming scheme.
					All your files must be accessible via http and start with the same
					<strong>Media File Base URL</strong> (e.g. <code>http://cdn.example.com/pod/</code>).
				</p>
				<p>
					Every episode is identified by a unique <strong>episode slug</strong>.
					This is an identifying lowercase name for an episode which is part of the media file url.
					A common pattern is to use the episode number (<code>001</code>, <code>002</code> etc.) or the number prefixed with
					the podcast mnemonic (<code>lh001</code>, <code>lh002</code> etc. if your podcast is "LoveHounds").
					A media file will be accessed by combining the media file base url, slug and <strong>file extension</strong>.
				</p>
				<p>
					Complete example: <code>http://cdn.example.com/pod/lh001.mp3</code>
				</p>
			</div>
			<div class="span6">
				<h3>Checklist</h3>
				<ol>
					<li>
						Copy all your files into the same publicly accessible directory.
					</li>
					<li>
						Depending on your current naming scheme you might or might not have to rename your files. If you are not sure, don't worry. It will be more obvious in the next step.
					</li>
					<li>
						Case sensitivity matters! To avoid conflicts, stick to one naming scheme.
					</li>
				</ol>
			</div>
		</div>

		<?php 
		$base_urls = array();
		$args = array( 'posts_per_page' => -1 );
		$query = new \WP_Query( $args );
		while( $query->have_posts() ) {
			$query->next_post();
			$enclosures = Enclosure::all_for_post( $query->post->ID );
			foreach ( $enclosures as $enclosure ) {
				$base_url = substr( $enclosure->url , 0, strrpos( $enclosure->url , "/" ) + 1 );
				if ( isset( $base_urls[ $base_url ] ) ) {
					$base_urls[ $base_url ]++;
				} else {
					$base_urls[ $base_url ] = 1;
				}
			}
		}

		arsort( $base_urls );

		$podcast = \Podlove\Modules\Migration\get_podcast_settings();
		?>

		<div class="row-fluid">
			<div class="span12">
				<h3>Basic Settings</h3>
				<form action="" class="form-horizontal">
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Podcast Title', 'podlove' ); ?></label>
						<div class="controls">
							<input type="text" class="input-xxlarge" name="podlove_migration[podcast][title]" value="<?php echo $podcast['title'] ?>">
						</div>
					</div>
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Podcast Subtitle', 'podlove' ); ?></label>
						<div class="controls">
							<input type="text" class="input-xxlarge" name="podlove_migration[podcast][subtitle]" value="<?php echo $podcast['subtitle'] ?>">
						</div>
					</div>
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Podcast Summary', 'podlove' ); ?></label>
						<div class="controls">
							<textarea name="podlove_migration[podcast][summary]" rows="3" class="input-xxlarge" placeholder="<?php echo __( 'A couple of sentences describing the podcast.', 'podlove' ); ?>"><?php echo $podcast['summary'] ?></textarea>
						</div>
					</div>
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Media File Base URL', 'podlove' ); ?></label>
						<div class="controls">
							<label class="radio">
								<input type="radio" name="podlove_migration[podcast][media_file_base_url_option]" value="preset" <?php checked( $podcast['media_file_base_url_option'], 'preset' ) ?>> 
								<select class="input-xxlarge" name="podlove_migration[podcast][media_file_base_url_preset]">
									<?php foreach ( $base_urls as $base_url => $count ): ?>
										<option value="<?php echo $base_url ?>" <?php echo selected( $podcast['media_file_base_url_preset'], $base_url ) ?>>
											<?php echo $base_url ?> (used in <?php echo $count ?> enclosures)
										</option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="radio">
								<input type="radio" name="podlove_migration[podcast][media_file_base_url_option]" value="custom" <?php checked( $podcast['media_file_base_url_option'], 'custom' ) ?>>
								<input type="text"  name="podlove_migration[podcast][media_file_base_url_custom]" value="<?php echo ( isset( $podcast['media_file_base_url_custom'] ) ? $podcast['media_file_base_url_custom'] : '' ) ?>" class="input-xxlarge" placeholder="http://cdn.example.com/pod/">
							</label>
						</div>

					</div>
					<div class="control-group">
						<div class="controls">
							<input type="submit" name="prev" class="btn" value="<?php echo __( 'Back', 'podlove' ) ?>">
							<input type="submit" class="btn btn-primary" value="<?php echo __( 'Continue', 'podlove' ) ?>">
						</div>
					</div>

					<input type="hidden" name="step" value="3">
					<input type="hidden" name="page" value="podlove_settings_migration_handle">

				</form>
			</div>
		</div>
		<?php
	}

}