<?php
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

		$wizard = array(
			new Wizard\StepWelcome,
			new Wizard\StepBasics,
			new Wizard\StepPosts,
			new Wizard\StepMigrate
		);

		// start-index must be 1, not 0
		array_unshift( $wizard, "whatever" );
		unset( $wizard[0] );

		$this->process_request();
		
		$steps = array_map( function($step){ return $step->title; }, $wizard );

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
			$wizard[ $current_step ]->template();
			?>
		</div>	
		<?php
	}

	public static function get_episode_slug( $post, $slug_type = 'wordpress' ) {

		switch ( $slug_type ) {
			case 'wordpress':
				return $post->post_name;
				break;
			case 'number':
				return self::get_number_slug( $post );
				break;
			case 'file':
				return self::get_file_slug( $post );
				break;

		}
	}

	public static function get_number_slug( $post ) {

		if ( preg_match( "/\d+/", \get_the_title( $post->ID ), $matches ) )
			return $matches[0];
		else
			return '';
	}

	public static function get_file_slug( $post ) {

		$enclosures = get_post_meta( $post->ID, 'enclosure', false );
		foreach ( $enclosures as $enclosure_data ) {
			$enclosure = Enclosure::from_enclosure_meta( $enclosure_data, $post->ID );

			if ( ! $enclosure->errors ) {
				$file_name = end( explode( "/", $enclosure->url ) );
				return current( explode( ".", $file_name ) );
			}
		}

	}

}