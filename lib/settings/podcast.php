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

			if ( $podcast['media_file_base_uri'] )
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

					$wrapper->subheader(
						__( 'Description', 'podlove' ),
						__( 'These are the three most important fields describing your podcast.
							<strong>Title</strong> is the title of the podcast that is the primary field to be used to represent the podcast in directories, lists and other uses.
							The <strong>subtitle</strong> is an extension to the title. The subtitle is meant to clarify what the podcast is about. While a title can be anything, a subtitle should be more descriptive in what the content actually wants to convey and what the most important information is, you want everybody want to know about the offering.
							A <strong>summary</strong> is a much more precise and elaborate description of the podcast\'s content. While title and subtitle are rather concise, a summary is meant to consist of one or more sentences that form a paragraph or more.', 'podlove' )
					);

					$wrapper->string( 'title', array(
						'label'       => __( 'Title', 'podlove' ),
						'html'        => array( 'class' => 'regular-text required' )
					) );

					$wrapper->string( 'subtitle', array(
						'label'       => __( 'Subtitle', 'podlove' ),
						'description' => __( 'Extension to the title. Clarify what the podcast is about.', 'podlove' ),
						'html'        => array( 'class' => 'regular-text' )
					) );

					$wrapper->text( 'summary', array(
						'label'       => __( 'Summary', 'podlove' ),
						'description' => __( 'Elaborate description of the podcast\'s content.', 'podlove' ),
						'html'        => array( 'rows' => 3, 'cols' => 40, 'class' => 'autogrow' )
					) );

					$wrapper->subheader(
						__( 'Media', 'podlove' ),
						__( 'The Podlove Publisher expects all your media files to be in the same <strong>Upload Location</strong>.
							It should be a publicly readable directory containing all media files.
							You should not create a separate directory for each episode.', 'podlove' )
					);

					$wrapper->string( 'media_file_base_uri', array(
						'label'       => __( 'Upload Location', 'podlove' ),
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

					$wrapper->subheader(
						__( 'License', 'podlove' )
					);

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

					$wrapper->subheader(
						__( 'Directory', 'podlove' ),
						__( 'You may provide additional information about your podcast that may or may not be used by podcast directories like iTunes.', 'podlove' )
					);

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
				});
				?>
				
			</form>
		</div>	
		<?php
	}
	
}