<?php
namespace Podlove\Settings;
use \Podlove\Model;

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
				postboxes.add_postbox_toggles( '<?php echo \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE; ?>' );
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

	/**
	 * Look for errors in podcast settings.
	 * 
	 * @return array list of error messages
	 */
	public static function get_podcast_setting_warnings() {
		
		$warnings = array();
		$podcast = Model\Podcast::get_instance();

		$required_attributes = array(
			'title'               => __( 'Your podcast needs a title.', 'podlove' ),
			'slug'                => __( 'Your podcast needs a mnemonic.', 'podlove' ),
			'media_file_base_uri' => __( 'Your podcast needs a base URL for file storage.', 'podlove' ),
		);
		$required_attributes = apply_filters( 'podlove_podcast_required_attributes', $required_attributes );

		foreach ( $required_attributes as $attribute => $error_text ) {
			if ( ! $podcast->$attribute )
				$warnings[] = $error_text;
		}

		return $warnings;
	}

	public static function validate_podcast_files() {
		
		$podcast = Model\Podcast::get_instance();
		$podcast_warnings = self::get_podcast_setting_warnings();

		if ( ! in_array( 'curl', get_loaded_extensions() ) ) {
			?>
			<div class="error">
				<p>
					<strong>ERROR: </strong>You need the <strong>curl PHP extension</strong> for Podlove to run properly.
					<br>
					If you think you can do it yourself, have a look at <a href="http://stackoverflow.com/questions/1347146/how-to-enable-curl-in-php">these instructions on how to enable curl in PHP</a>.
				</p>
			</div>
			<?php
		}

		?>
		<div id="validation">

			<?php if ( count( $podcast_warnings ) ): ?>
				<style type="text/css">
				#podcast_warnings {
					color: #333333;
					background-color: #FFEBE8;
					border: 1px solid #CC0000;
					border-radius: 3px;
					padding: 0.4em 1.0em;
					margin: 10px 0px;
				}
				#podcast_warnings h4 {
					margin: 10px 0px;
				}
				</style>
				<div id="podcast_warnings">
					<h4><?php echo __( 'Critical Notes' ) ?></h4>
					<?php foreach ( $podcast_warnings as $warning ): ?>
						<div class="line">
							<?php echo $warning ?>
							<a href="<?php echo admin_url( 'admin.php?page=podlove_settings_podcast_handle' ) ?>">
								<?php echo __( 'go fix it', 'podlove' ) ?>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<a href="#" id="validate_everything">
				<?php echo __( 'Validate Everything', 'podlove' ); ?>
			</a>

			<?php
			echo "<h4>" . $podcast->title . "</h4>";
			$episodes = Model\Episode::all();
			?>

			<?php foreach ( $episodes as $episode ): ?>
				<?php
				$post_id = $episode->post_id;
				?>
				<div class="episode">
					<div class="slug">
						<strong><?php echo sprintf( "%s (%s)", get_the_title( $post_id ), $episode->slug ); ?></strong>
					</div>
					<div class="duration">
						<?php echo sprintf( __( 'Duration: %s', 'podlove' ), ( $episode->duration ) ? $episode->duration : __( '<span class="warning">empty</span>', 'podlove' ) ); ?>
					</div>
					<div class="chapters">
						<?php echo sprintf( __( 'Chapters: %s' ), strlen( $episode->chapters ) > 0 ? __( 'existing', 'podlove' ) : __( '<span class="warning">empty</span>', 'podlove' ) ); ?>
					</div>
					<?php if ( $podcast->supports_cover_art ): ?>
						<div class="coverart">
							<?php echo sprintf( __( 'Cover Art: %s' ), strlen( $episode->get_cover_art() ) > 0 ? __( 'existing', 'podlove' ) : __( '<span class="warning">empty</span>', 'podlove' ) ); ?>
						</div>
					<?php endif; ?>
					<div class="media_files">
						<?php $media_files = $episode->media_files(); ?>
						<?php foreach ( $media_files as $media_file ): ?>
							<div class="file" data-id="<?php echo $media_file->id; ?>">
								<span class="status">
									<?php if ( $media_file->size <= 0 ): ?>
										<?php echo __( "<span class=\"error\">filesize missing</span>", 'podlove' ); ?>
									<?php endif ?>
								</span>
								<?php if ( $media_location = $media_file->media_location() ): ?>
									<span class="title"><?php echo $media_location->title() ?></span>
								<?php endif; ?>
								<span class="url">
									<?php echo $media_file->get_file_url(); ?>
								</span>
							</div>
						<?php endforeach ?>
					</div>
				</div>
			<?php endforeach ?>
		</div>

		<style type="text/css">
		#validation h4 {
			font-size: 20px;
		}

		#validation .episode {
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