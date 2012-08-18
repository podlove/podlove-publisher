<?php
namespace Podlove\Settings;

class Dashboard {

	static $pagehook;

	public function __construct() {

		// use \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE to replace
		// default first item name
		Dashboard::$pagehook = add_submenu_page(
			/* $parent_slug*/ \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE,
			/* $page_title */ __( 'Dashboard', 'podlove' ),
			/* $menu_title */ __( 'Dashboard', 'podlove' ),
			/* $capability */ 'administrator',
			/* $menu_slug  */ \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE,
			/* $function   */ array( $this, 'settings_page' )
		);

		add_action( Dashboard::$pagehook, function () {
			wp_enqueue_script( 'postbox' );
			add_screen_option( 'layout_columns', array(
				'max' => 2, 'default' => 2
			) );
		} );

	}

	public static function about_meta() {
		?>
		Podlove rocks 😸
		<?php
	}

	public static function settings_page() {
		add_meta_box( Dashboard::$pagehook . '_about', __( 'About', 'podlove' ), '\Podlove\Settings\Dashboard::about_meta', Dashboard::$pagehook, 'side' );		
		add_meta_box( Dashboard::$pagehook . '_validation', __( 'Validate Podcast Files', 'podlove' ), '\Podlove\Settings\Dashboard::validate_podcast_files', Dashboard::$pagehook, 'normal' );

		do_action( 'podlove_dashboard_meta_boxes' );

		?>
		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>
			<h2><?php echo __( 'Podlove Dashboard', 'podlove' ); ?></h2>

			<div id="poststuff" class="metabox-holder has-right-sidebar">
				
				<!-- sidebar -->
				<div id="side-info-column" class="inner-sidebar">
					<?php do_action( 'podlove_settings_before_sidebar_boxes' ); ?>
					<?php do_meta_boxes( Dashboard::$pagehook, 'side', NULL ); ?>
					<?php do_action( 'podlove_settings_after_sidebar_boxes' ); ?>
				</div>

				<!-- main -->
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_action( 'podlove_settings_before_main_boxes' ); ?>
						<?php do_meta_boxes( Dashboard::$pagehook, 'normal', NULL ); ?>
						<?php do_meta_boxes( Dashboard::$pagehook, 'additional', NULL ); ?>
						<?php do_action( 'podlove_settings_after_main_boxes' ); ?>						
					</div>
				</div>

				<br class="clear"/>

			</div>

			<!-- Stuff for opening / closing metaboxes -->
			<script type="text/javascript">
			jQuery( document ).ready( function( $ ){
				// close postboxes that should be closed
				$( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
				// postboxes setup
				postboxes.add_postbox_toggles( '<?php echo Podcast_Post_Type::SETTINGS_PAGE_HANDLE; ?>' );
			} );
			</script>

			<form style='display: none' method='get' action=''>
				<?php
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
				?>
			</form>

		</div>
		<?php
	}

	public static function validate_podcast_files() {
		$shows = \Podlove\Model\Show::all();

		if ( ! in_array( 'curl', get_loaded_extensions() ) ) {
			?>
			<div class="error"><p><strong>ERROR: </strong>You need curl for Podlove to run properly.</p></div>
			<?php
		}

		?>
		<div id="validation">

			<a href="#" id="validate_everything">
				<?php echo __( 'Validate Everything', 'podlove' ); ?>
			</a>

			<?php foreach ( $shows as $show ): ?>
				<?php
				echo "<h4>" . $show->name . "</h4>";
				$releases = $show->releases();
				?>

				<?php foreach ( $releases as $release ): ?>
					<?php
					$episode = $release->episode();
					$post_id = $episode->post_id;
					?>
					<div class="release">
						<div class="slug">
							<strong><?php echo sprintf( "%s (%s)", get_the_title( $post_id ), $release->slug ); ?></strong>
						</div>
						<div class="duration">
							<?php echo sprintf( __( 'Duration: %s', 'podlove' ), ( $release->duration ) ? $release->duration : __( '<span class="warning">empty</span>', 'podlove' ) ); ?>
						</div>
						<div class="chapters">
							<?php echo sprintf( __( 'Chapters: %s' ), strlen( $release->chapters ) > 0 ? __( 'existing', 'podlove' ) : __( '<span class="warning">empty</span>', 'podlove' ) ); ?>
						</div>
						<?php if ( $show->supports_cover_art ): ?>
							<div class="coverart">
								<?php echo sprintf( __( 'Cover Art: %s' ), strlen( $release->cover_art ) > 0 ? __( 'existing', 'podlove' ) : __( '<span class="warning">empty</span>', 'podlove' ) ); ?>
							</div>
						<?php endif; ?>
						<div class="media_files">
							<?php $media_files = $release->media_files(); ?>
							<?php foreach ( $media_files as $media_file ): ?>
								<div class="file" data-id="<?php echo $media_file->id; ?>">
									<span class="status">
										<?php if ( $media_file->size <= 0 ): ?>
											<?php echo __( "<span class=\"error\">filesize missing</span>", 'podlove' ); ?>
										<?php endif ?>
									</span>
									<span class="title"><?php echo $media_file->media_location()->title() ?></span>
									<span class="url">
										<?php echo $media_file->get_file_url(); ?>
									</span>
								</div>
							<?php endforeach ?>
						</div>
					</div>
				<?php endforeach ?>
			<?php endforeach ?>			
		</div>

		<style type="text/css">
		#validation h4 {
			font-size: 20px;
		}

		#validation .release {
			margin: 0 0 15px 0;
		}

		#validation .slug {
			font-size: 18px;
			margin: 0 0 5px 0;
		}

		#validation .warning {
			color: maroon;
		}

		#validation .error {
			color: red;
		}
		</style>
		<?php
	}

}