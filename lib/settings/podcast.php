<?php
namespace Podlove\Settings;
use \Podlove\Model;

class Podcast {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Podcast::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Podcast Settings',
			/* $menu_title */ 'Podcast Settings',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_podcast_handle',
			/* $function   */ array( $this, 'page' )
		);

		add_settings_section(
			/* $id 		 */ 'podlove_podcast_general',
			/* $title 	 */ __( 'Podcast Settings', 'podlove' ),	
			/* $callback */ function () { /* section head html */ }, 		
			/* $page	 */ Podcast::$pagehook	
		);

		register_setting( Podcast::$pagehook, 'podlove_podcast', function( $podcast ) {
			$podcast['media_file_base_uri'] = trailingslashit( $podcast['media_file_base_uri'] );
			return $podcast;
		} );
	}
	
	function page() {
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Podcast Settings' ) ?></h2>

			<form method="post" action="options.php">
				<?php settings_fields( Podcast::$pagehook ); ?>

				<?php
				$podcast = \Podlove\Model\Podcast::get_instance();

				$form_attributes = array(
					'context'    => 'podlove_podcast',
					'form'       => false
				);

				\Podlove\Form\build_for( $podcast, $form_attributes, function ( $form ) {
					$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
					$podcast = $form->object;

					$wrapper->string( 'title', array(
						'label'       => __( 'Title', 'podlove' ),
						'description' => __( '', 'podlove' ),
						'html'        => array( 'class' => 'regular-text required' )
					) );

					$wrapper->string( 'subtitle', array(
						'label'       => __( 'Subtitle', 'podlove' ),
						'description' => __( 'The subtitle is used by iTunes.', 'podlove' ),
						'html'        => array( 'class' => 'regular-text' )
					) );

					$wrapper->text( 'summary', array(
						'label'       => __( 'Summary', 'podlove' ),
						'description' => __( 'A couple of sentences describing the podcast.', 'podlove' ),
						'html'        => array( 'rows' => 5, 'cols' => 40 )
					) );

					$wrapper->string( 'slug', array(
						'label'       => __( 'Mnemonic', 'podlove' ),
						'description' => __( 'The abbreviation for your podcast. Commonly the initials of the title.', 'podlove' ),
						'html'        => array( 'class' => 'regular-text required' )
					) );

					$wrapper->string( 'media_file_base_uri', array(
						'label'       => __( 'Media File Base URL', 'podlove' ),
						'description' => __( 'Example: http://cdn.example.com/pod/', 'podlove' ),
						'html' => array( 'class' => 'regular-text required' )
					) );

					$wrapper->image( 'cover_image', array(
						'label'        => __( 'Cover Art URL', 'podlove' ),
						'description'  => __( 'JPEG or PNG. At least 1400 x 1400 pixels.', 'podlove' ),
						'html'         => array( 'class' => 'regular-text' ),
						'image_width'  => 300,
						'image_height' => 300
					) );

					$wrapper->string( 'author_name', array(
						'label'       => __( 'Author Name', 'podlove' ),
						'description' => __( 'Publicly displayed in Podcast directories.', 'podlove' ),
						'html' => array( 'class' => 'regular-text' )
					) );

					$wrapper->string( 'publisher_name', array(
						'label'       => __( 'Publisher Name', 'podlove' ),
						'description' => __( '', 'podlove' ),
						'html' => array( 'class' => 'regular-text' )
					) );

					$wrapper->string( 'publisher_url', array(
						'label'       => __( 'Publisher URL', 'podlove' ),
						'description' => __( '', 'podlove' ),
						'html' => array( 'class' => 'regular-text' )
					) );

					$wrapper->string( 'license_name', array(
						'label'       => __( 'License Name', 'podlove' ),
						'description' => __( 'Example: CC BY 3.0', 'podlove' ),
						'html' => array( 'class' => 'regular-text' )
					) );

					$wrapper->string( 'license_url', array(
						'label'       => __( 'License URL', 'podlove' ),
						'description' => __( 'Example: http://creativecommons.org/licenses/by/3.0/', 'podlove' ),
						'html' => array( 'class' => 'regular-text' )
					) );
			
					$wrapper->string( 'owner_name', array(
						'label'       => __( 'Owner Name', 'podlove' ),
						'description' => __( 'Used by iTunes and other Podcast directories to contact you.', 'podlove' ),
						'html' => array( 'class' => 'regular-text' )
					) );
			
					$wrapper->string( 'owner_email', array(
						'label'       => __( 'Owner Email', 'podlove' ),
						'description' => __( 'Used by iTunes and other Podcast directories to contact you.', 'podlove' ),
						'html' => array( 'class' => 'regular-text' )
					) );
			
					$wrapper->string( 'keywords', array(
						'label'       => __( 'Keywords', 'podlove' ),
						'description' => __( 'List of keywords. Separate with commas.', 'podlove' ),
						'html' => array( 'class' => 'regular-text' )
					) );

					$wrapper->select( 'category_1', array(
						'label'       => __( 'iTunes Categories', 'podlove' ),
						'description' => '',
						'type'     => 'select',
						'options'  => \Podlove\Itunes\categories()
					) );

					$wrapper->select( 'category_2', array(
						'label'       => '',
						'description' => '',
						'type'     => 'select',
						'options'  => \Podlove\Itunes\categories()
					) );

					$wrapper->select( 'category_3', array(
						'label'       => '',
						'description' => '<br>'
						                 . __( 'For placement within the older, text-based browse system, podcast feeds may list up to 3 category/subcategory pairs. (For example, "Music" counts as 1, as does "Business > Careers.") For placement within the newer browse system based on Category links, however, and for placement within the Top Podcasts and Top Episodes lists that appear in the right column of most podcast pages, only the first category listed in the feed is used.' )
						                 . ' (<a href="http://www.apple.com/itunes/podcasts/specs.html#category" target="_blank">http://www.apple.com/itunes/podcasts/specs.html#category</a>)',
						'options'  => \Podlove\Itunes\categories()
					) );

					$wrapper->select( 'language', array(
						'label'       => __( 'Language', 'podlove' ),
						'description' => __( '', 'podlove' ),
						'default'     => get_bloginfo( 'language' ),
						'options'  => \Podlove\Locale\locales()
					) );

					$wrapper->select( 'explicit', array(
						'label'       => __( 'Explicit Content?', 'podlove' ),
						'description' => __( '', 'podlove' ),
						'type'    => 'checkbox',
		                'options'  => array(0 => 'no', 1 => 'yes', 2 => 'clean')
					) );

					$wrapper->string( 'url_template', array(
						'label'       => __( 'Episode Asset URL Template', 'podlove' ),
						'description' => __( 'You probably don\'t want to change this.', 'podlove' ),
						'html' => array( 'class' => 'large-text required' )
					) );
				});
				?>
				
			</form>
		</div>	
		<?php
	}
	
}