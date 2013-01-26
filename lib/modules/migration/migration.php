<?php
namespace Podlove\Modules\Migration;
use \Podlove\Model;

class Migration extends \Podlove\Modules\Base {

		protected $module_name = 'Migration';
		protected $module_description = 'Helps you migrate from PodPress/PowerPress/... to Podlove.';

		public function load() {
			add_action( 'wp', array( $this, 'register_hooks' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_styles' ) );
			add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		}

		public function register_admin_styles() {
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'podlove_settings_migration_handle' ) {
				wp_register_style( 'twitter-bootstrap-style', $this->get_module_url() . '/css/bootstrap.min.css' );
				wp_enqueue_style( 'twitter-bootstrap-style' );
			}
		}

		public function register_menu() {
			new Settings\Assistant( \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE );
		}

		/**
		 * Register hooks on episode pages only.
		 */
		public function register_hooks() {
			
			if ( ! is_single() )
				return;

			if ( 'podcast' !== get_post_type() )
				return;

			add_filter( 'language_attributes', function ( $output = '' ) {
				return $output . ' prefix="og: http://ogp.me/ns#"';
			} );

			add_action( 'wp_head', array( $this, 'insert_migration_metadata' ) );
		}	
}

class Enclosure {

	public $url;
	public $duration;
	public $mime_type;
	public $content_length;
	public $file_type;
	public $extension;
	public $errors = array();

	public function __construct() {

	}

	/**
	 * Takes a WordPress enclosure data blob and wraps it in a convenient API.
	 * 
	 * @param  string $url
	 * @param  int    $post_id
	 * @return Enclosure
	 */
	public static function from_enclosure_meta( $enclosure_meta, $post_id = NULL ) {

		$enclosure = new self();

		$enc_data   = explode( "\n", $enclosure_meta );
		$mime_data  = preg_split('/[ \t]/', trim( $enc_data[2] ) );
		$extra_data = ( isset( $enc_data[3] ) ) ? unserialize( $enc_data[3] ) : array();

		if ( is_array( $extra_data ) && array_key_exists( 'duration', $extra_data ) )
			$enclosure->duration  = trim( $extra_data['duration'] );

		$enclosure->post_id        = $post_id;
		$enclosure->url            = trim( $enc_data[0] );
		$enclosure->content_length = trim( $enc_data[1] );
		$enclosure->mime_type      = trim( $mime_data[0] );
		$enclosure->file_type      = Model\FileType::find_one_by_mime_type( $enclosure->mime_type );

		$enclosure->extension      = pathinfo( $enclosure->url, PATHINFO_EXTENSION );

		if ( ! $enclosure->file_type ) {
			$enclosure->errors[] = sprintf(
				__( '<strong>Unknown mime type "%s"</strong> in post %s If you want to migrate files with this mime type, you need to create your own %sfile type%s', 'podlove' ),
				$enclosure->mime_type,
				sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link( $enclosure->post_id ), get_the_title( $enclosure->post_id ) ),
				'<a href="?page=podlove_file_types_settings_handle" target="_blank">',
				'</a>'
			);
			return $enclosure;	
		}

		return $enclosure;
	}

	/**
	 * Takes a URL and extracts some information from it.
	 *
	 * Right now it does *not* actually request the URL to get further information.
	 * 
	 * @param  string $url
	 * @param  int    $post_id
	 * @return Enclosure
	 */
	public static function from_url( $url, $post_id = NULL ) {
		
		$enclosure = new self();

		$enclosure->post_id = $post_id;
		$enclosure->url     = $url;

		$enclosure->extension = pathinfo( $enclosure->url, PATHINFO_EXTENSION );
		$enclosure->file_type = Model\FileType::find_one_by_extension( $enclosure->extension );

		if ( filter_var( $enclosure->url, FILTER_VALIDATE_URL ) === false  ) {
			$this->errors[] = sprintf(
				'Invalid URL for enclosure in %s',
				sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link( $this->post_id ), get_the_title( $this->post_id ) )
			);
			return $enclosure;
		}

		if ( ! $enclosure->file_type ) {
			$errors[] = sprintf(
				__( '<strong>Unknown extension "%s"</strong> in post %s If you want to migrate files with this extension, you need to create your own %sfile type%s', 'podlove' ),
				$enclosure->extension,
				sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link( $this->post_id ), get_the_title( $this->post_id ) ),
				'<a href="?page=podlove_file_types_settings_handle" target="_blank">',
				'</a>'
			);
			return $enclosure;
		}

		return $enclosure;
	}

}

namespace Podlove\Modules\Migration\Settings;
use \Podlove\Modules\Migration\Migration;
use \Podlove\Modules\Migration\Enclosure;
use \Podlove\Model;

class Assistant {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Assistant::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Migration Assistant',
			/* $menu_title */ 'Migration Assistant',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_migration_handle',
			/* $function   */ array( $this, 'page' )
		);

	}

	public static function get_page_link( $step = 1 ) {
		return sprintf( '?page=%s&step=%s', 'podlove_settings_migration_handle', $step );
	}

	public function process_request() {
		
		if ( ! isset( $_REQUEST['podlove_migration'] ) )
			return;

		$migration_settings = get_option( 'podlove_migration', array() );

		foreach ( $_REQUEST['podlove_migration'] as $setting_key => $setting_value ) {
			$migration_settings[ $setting_key ] = $setting_value;
		}

		update_option( 'podlove_migration', $migration_settings );
	}

	public function page() {

		$this->process_request();

		$steps = array(
			1 => __( 'Welcome', 'podlove' ),
			2 => __( 'Post Selection', 'podlove' ),
			3 => __( 'Basic Settings', 'podlove' ),
			4 => __( 'Verify & Migrate', 'podlove' ),
			5 => __( 'Finishing Gloss', 'podlove' ),
		);

		if ( isset( $_REQUEST['step'] ) && $_REQUEST['step'] > 0 && $_REQUEST['step'] <= count( $steps ) ) {
			$current_step = (int) $_REQUEST['step'];
			Migration::instance()->update_module_option( 'current_step', $current_step );
		} else {
			$current_step = Migration::instance()->get_module_option( 'current_step', 1 );
		}

		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Migration Assistant' ) ?></h2>
			<hr>

			<ul class="nav nav-pills">
				<?php foreach ( $steps as $index => $title ): ?>
					<?php
					$class = $index === $current_step ? 'active' : ( $current_step < $index ? 'disabled' : '' );
					$title = sprintf( __( 'Step %s:', 'podlove' ), $index ) . ' ' . $title;
					$link  = ( $class == 'disabled' ) ? "#" : self::get_page_link( $index );
					?>
					<li class="<?php echo $class ?>">
						<a href="<?php echo $link ?>"><?php echo $title ?></a>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php 
			$this->display_step( $current_step );
			?>
		</div>	
		<?php
	}

	public function display_step( $step ) {
		if ( is_callable( array( $this, "step_$step" ) ) ) {
			call_user_func( array( $this, "step_$step" ) );
		} else {
			?>
			<div class="alert alert-error">
				<strong>Whoops!</strong> This page doesn't exist :(
			</div>
			<?php
		}
	}

	public function step_1() {
		?>
		<div class="hero-unit">
			<h1>
				<?php echo __( 'Hi, Let\'s Migrate!', 'podlove' ); ?>
			</h1>
			<p>
				<?php echo __( 'My name is Miggy and I\'m your Migration Assistant for today. Cool, huh?
				I\'m able to help you if you\'re currently using PodPress, PowerPress or any other podcasting setup which manages episodes as posts with enclosures.', 'podlove' ); ?>
			</p>
			<p>
				<?php echo __( 'Before we start, <strong><em>please backup your database!</em></strong>
				I won\'t edit or delete any of your existing data but, you know, nobody has ever lost any data by backing up. Play it safe.', 'podlove' ); ?>
			</p>
			<p>
				<a href="<?php echo self::get_page_link( 2 ) ?>" class="btn btn-primary btn-large">
					<?php echo __( 'Let\'s do This!', 'podlove' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
	
	public function step_2() {

		$args = array(
			'meta_key'       => 'enclosure',
			'posts_per_page' => -1
		);

		$episodes   = array();
		$file_types = array();
		$errors     = array();

		$query = new \WP_Query( $args );
		while( $query->have_posts() ) {
			$query->next_post();
			$episodes[] = $query->post;

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

			// process enclosures
			$enclosures = get_post_meta( $query->post->ID, 'enclosure', false );
			foreach ( $enclosures as $enclosure_data ) {
				$enclosure = Enclosure::from_enclosure_meta( $enclosure_data, $query->post->ID );

				if ( $enclosure->errors ) {
					$errors = array_merge( $enclosure->errors, $errors );
				} elseif ( isset( $file_types[ $enclosure->file_type->id ] ) ) {
					$file_types[ $enclosure->file_type->id ]['count'] += 1;
				} else {
					$file_types[ $enclosure->file_type->id ] = array( 'file_type' => $enclosure->file_type, 'count' => 1 );
				}

			}
		}

		if ( ! count( $episodes ) )
			$errors[] = __( '<strong>No Episodes Found!</strong> I only know how to migrate episodes with enclosures. However, I could\'t find any. Sorry!' , 'podlove' );

		$migration_settings = get_option( 'podlove_migration', array() );
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
				<form action="" method="POST">

					<input type="hidden" name="step" value="3">
					<input type="hidden" name="page" value="podlove_settings_migration_handle">

					<input type="submit" class="btn btn-primary" value="<?php echo __( 'Save and Continue', 'podlove' ) ?>">

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
						 		<th>
						 			<?php echo __( 'WordPress Slug', 'podlove' ) ?>
						 		</th>
						 	</tr>
						 </thead>
						 <tbody>
						 	<?php foreach ( $episodes as $episode_post ): ?>
						 		<tr>
						 			<td>
						 				<input type="checkbox" <?php checked( isset( $migration_settings['episodes'][ $episode_post->ID ] ) ) ?> name="podlove_migration[episodes][<?php echo $episode_post->ID ?>]">
						 			</td>
						 			<td>
						 				<?php echo $episode_post->ID ?>
						 			</td>
						 			<td>
						 				<a href="<?php echo get_edit_post_link( $episode_post->ID ) ?>" target="_blank">
							 				<?php echo \get_the_title( $episode_post->ID ) ?>
						 				</a>
						 			</td>
						 			<td>
						 				<?php echo $episode_post->post_name ?>
						 			</td>
						 		</tr>
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

	public function step_3() {
		?>
		<div class="row-fluid">
			<div class="span12">
				<div class="well">
					<p>
						<?php echo __( 'Now, please tell me about your podcast. What is it about?', 'podlove' ); ?>
					</p>
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

		<div class="row-fluid">
			<div class="span12">
				<form action="" class="form-horizontal">
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Podcast Title', 'podlove' ); ?></label>
						<div class="controls">
							<input type="text" class="input-xlarge">
						</div>
					</div>
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Podcast Subtitle', 'podlove' ); ?></label>
						<div class="controls">
							<input type="text" class="input-xlarge">
						</div>
					</div>
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Podcast Summary', 'podlove' ); ?></label>
						<div class="controls">
							<textarea name="" rows="3" class="input-xlarge" placeholder="<?php echo __( 'A couple of sentences describing the podcast.', 'podlove' ); ?>"></textarea>
						</div>
					</div>
					<div class="control-group">
						<label for="" class="control-label"><?php echo __( 'Media File Base URL', 'podlove' ); ?></label>
						<div class="controls">
							<input type="text" class="input-xlarge" placeholder="http://cdn.example.com/pod/">
						</div>
					</div>
					<div class="control-group">
						<div class="controls">
							<input type="submit" class="btn btn-primary" value="<?php echo __( 'Save and Continue', 'podlove' ) ?>">
						</div>
					</div>

					<input type="hidden" name="step" value="4">
					<input type="hidden" name="page" value="podlove_settings_migration_handle">

				</form>
			</div>
		</div>
		<?php
	}

}