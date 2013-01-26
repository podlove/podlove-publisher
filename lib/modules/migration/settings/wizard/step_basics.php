<?php
namespace Podlove\Modules\Migration\Settings\Wizard;

class StepBasics extends Step {

	public $title = 'Basic Settings';
	
	public function template() {
		?>
		<div class="row-fluid">
			<div class="span12">
				<div class="well">
					<!-- <p>
						<?php echo __( 'Now, please tell me about your podcast. What is it about?', 'podlove' ); ?>
					</p> -->
					<p>
						Here's the tricky part.
						Please read carefully, I will now describe core concept of the Podlove Publisher.
					</p>
					<p>
						The Publisher assumes all your media files are in the same directory and follow
						a certain naming scheme.
						All your files must be accessible via http and start with what I call the
						<strong>Media File Base URL</strong> — that might be <em>http://cdn.example.com/pod/</em>.
						Every episode is identified by a unique <strong>slug</strong>.
						A common pattern is to use the episode number (001, 002 etc.) or the number prefixed with
						the podcast mnemonic (ms001, ms002 etc. if you podcasts name is "MacSmack").
						A media file can be accessed by combining the media file base url, slug and <strong>file extension</strong>.
						So a complete example URL might look like this: <em>http://cdn.example.com/pod/ms001.mp3</em>
					</p>
					<p>
						So here's a checklist for you to make sure the migration works smoothly:

						<ol>
							<li>
								Copy all your files into the same directory and make sure they are accessible.
							</li>
							<li>
								Depending on your current naming scheme you might or might not have a slug.
								If your slug is either part of the file url or blog post url, I can find it automatically.
								Otherwise you will have to write the slug into all episodes manually after the migration.
							</li>
							<li>
								Case sensitivity matters! To avoid all conflicts, just write everything in small letters.
							</li>
						</ol>
					</p>
				</div>
			</div>
		</div>

		<?php 
		$migration_settings = get_option( 'podlove_migration', array() );
		if ( isset( $migration_settings['podcast'] ) ) {
			$podcast = $migration_settings['podcast'];
		} else {
			$podcast = array();
		}
		?>

		<div class="row-fluid">
			<div class="span12">
				<form action="" class="form-horizontal">
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Podcast Title', 'podlove' ); ?></label>
						<div class="controls">
							<input type="text" class="input-xlarge" name="podlove_migration[podcast][title]" value="<?php echo $podcast['title'] ?>">
						</div>
					</div>
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Podcast Subtitle', 'podlove' ); ?></label>
						<div class="controls">
							<input type="text" class="input-xlarge" name="podlove_migration[podcast][subtitle]" value="<?php echo $podcast['subtitle'] ?>">
						</div>
					</div>
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Podcast Summary', 'podlove' ); ?></label>
						<div class="controls">
							<textarea name="podlove_migration[podcast][summary]" rows="3" class="input-xlarge" placeholder="<?php echo __( 'A couple of sentences describing the podcast.', 'podlove' ); ?>"><?php echo $podcast['summary'] ?></textarea>
						</div>
					</div>
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Media File Base URL', 'podlove' ); ?></label>
						<div class="controls">
							<input type="text" name="podlove_migration[podcast][media_file_base_url]" value="<?php echo $podcast['media_file_base_url'] ?>" class="input-xlarge" placeholder="http://cdn.example.com/pod/">
						</div>
					</div>
					<div class="control-group">
						<div class="controls">
							<input type="submit" class="btn btn-primary" value="<?php echo __( 'Save and Continue', 'podlove' ) ?>">
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