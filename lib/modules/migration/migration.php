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

	/**
	 * Takes a WordPress enclosure data blob and wraps
	 * it in a convenient API.
	 * 
	 * @param string $data value of WordPress enclosure post_meta
	 */
	public function __construct( $enclosure ) {
		$enc_data   = explode( "\n", $enclosure );
		$mime_data  = preg_split('/[ \t]/', trim( $enc_data[2] ) );
		$extra_data = ( isset( $enc_data[3] ) ) ? unserialize( $enc_data[3] ) : array();

		if ( is_array( $extra_data ) && array_key_exists( 'duration', $extra_data ) )
			$this->duration  = trim( $extra_data['duration'] );

		$this->url            = trim( $enc_data[0] );
		$this->content_length = trim( $enc_data[1] );
		$this->mime_type      = trim( $mime_data[0] );
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

	public function page() {

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

			$enclosures = get_post_meta( $query->post->ID, 'enclosure', false );
			foreach ( $enclosures as $enclosure_data ) {
				$enclosure = new Enclosure( $enclosure_data );

				if ( ! $duration = get_post_meta( $query->post->ID, 'duration', true ) )
					$duration  = $enclosure->duration;

				$file_type = Model\FileType::find_one_by_mime_type( $enclosure->mime_type );
				if ( $file_type ) {
					$file_types[ $file_type->id ] = $file_type;
				} else {
					$errors[] = sprintf(
						__( '<strong>Unknown mime type "%s"</strong> in post %s If you want to migrate files with this mime type, you need to create your own %sfile type%s', 'podlove' ),
						$enclosure->mime_type,
						sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link( $query->post->ID ), get_the_title( $query->post->ID ) ),
						'<a href="?page=podlove_file_types_settings_handle" target="_blank">',
						'</a>'
					);
				}

				// $file_types[] = array( $url, $length, $mime_type, $duration );
			}
		}

		if ( ! count( $episodes ) )
			$errors[] = __( '<strong>No Episodes Found!</strong> I only know how to migrate episodes with enclosures. However, I could\'t find any. Sorry!' , 'podlove' );

		?>
		<div class="row-fluid">
			<div class="span12">

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
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $file_types as $file_type ): ?>
							<tr>
								<td>
									<input type="checkbox" checked="checked">
								</td>
								<td>
									<?php echo $file_type->name ?>
								</td>
								<td>
									<?php echo $file_type->mime_type ?>
								</td>
								<td>
									<?php echo $file_type->extension ?>
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
					 	</tr>
					 </thead>
					 <tbody>
					 	<?php foreach ( $episodes as $episode_post ): ?>
					 		<tr>
					 			<td>
					 				<input type="checkbox" checked="checked">
					 			</td>
					 			<td>
					 				<?php echo $episode_post->ID ?>
					 			</td>
					 			<td>
					 				<a href="<?php echo get_edit_post_link( $episode_post->ID ) ?>" target="_blank">
						 				<?php echo \get_the_title( $episode_post->ID ) ?>
					 				</a>
					 			</td>
					 		</tr>
						<?php endforeach; ?>
					 </tbody>
				</table>
				
			</div>
		</div>
		<?php

		// Restore original Query & Post Data
		wp_reset_query();
		wp_reset_postdata();
	}

}