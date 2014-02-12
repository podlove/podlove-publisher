<?php
namespace Podlove\Settings;
use \Podlove\Model;

use \Podlove\Settings\Expert\Tabs;
use \Podlove\Settings\Podcast\Tab;

class Podcast {

	static $pagehook;
	private $tabs;
	
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

		$tabs = new Tabs( __( 'Podcast Settings', 'podlove' ) );
		$tabs->addTab( new Tab\Description( __( 'Description', 'podlove' ), true ) );
		$tabs->addTab( new Tab\Media( __( 'Media', 'podlove' ) ) );
		$tabs->addTab( new Tab\License( __( 'License', 'podlove' ) ) );
		$tabs->addTab( new Tab\Directory( __( 'Directory', 'podlove' ) ) );
		$this->tabs = $tabs;
		$this->tabs->initCurrentTab();
	}
	
	function page() {
		?>
		<div class="wrap">

			<?php 
			echo $this->tabs->getTabsHTML();
			echo $this->tabs->getCurrentTabPage();
			?>

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

					// $wrapper->subheader(
					// 	__( 'Description', 'podlove' ),
					// 	__( 'These are the three most important fields describing your podcast.
					// 		<strong>Title</strong> is the title of the podcast that is the primary field to be used to represent the podcast in directories, lists and other uses.
					// 		The <strong>subtitle</strong> is an extension to the title. The subtitle is meant to clarify what the podcast is about. While a title can be anything, a subtitle should be more descriptive in what the content actually wants to convey and what the most important information is, you want everybody want to know about the offering.
					// 		A <strong>summary</strong> is a much more precise and elaborate description of the podcast\'s content. While title and subtitle are rather concise, a summary is meant to consist of one or more sentences that form a paragraph or more.', 'podlove' )
					// );

					// $wrapper->string( 'title', array(
					// 	'label'       => __( 'Title', 'podlove' ),
					// 	'html'        => array( 'class' => 'regular-text required' )
					// ) );

					// $wrapper->string( 'subtitle', array(
					// 	'label'       => __( 'Subtitle', 'podlove' ),
					// 	'description' => __( 'Extension to the title. Clarify what the podcast is about.', 'podlove' ),
					// 	'html'        => array( 'class' => 'regular-text' )
					// ) );

					// $wrapper->text( 'summary', array(
					// 	'label'       => __( 'Summary', 'podlove' ),
					// 	'description' => __( 'Elaborate description of the podcast\'s content.', 'podlove' ),
					// 	'html'        => array( 'rows' => 3, 'cols' => 40, 'class' => 'autogrow' )
					// ) );

					// $wrapper->select( 'language', array(
					// 	'label'       => __( 'Language', 'podlove' ),
					// 	'description' => __( '', 'podlove' ),
					// 	'default'     => get_bloginfo( 'language' ),
					// 	'options'  => \Podlove\Locale\locales()
					// ) );

					// $wrapper->subheader(
					// 	__( 'Media', 'podlove' ),
					// 	__( 'The Podlove Publisher expects all your media files to be in the same <strong>Upload Location</strong>.
					// 		It should be a publicly readable directory containing all media files.
					// 		You should not create a separate directory for each episode.', 'podlove' )
					// );

					// $wrapper->string( 'media_file_base_uri', array(
					// 	'label'       => __( 'Upload Location', 'podlove' ),
					// 	'description' => __( 'Example: http://cdn.example.com/pod/', 'podlove' ),
					// 	'html' => array( 'class' => 'regular-text required' )
					// ) );

					// $wrapper->image( 'cover_image', array(
					// 	'label'        => __( 'Cover Art URL', 'podlove' ),
					// 	'description'  => __( 'JPEG or PNG. At least 1400 x 1400 pixels.', 'podlove' ),
					// 	'html'         => array( 'class' => 'regular-text' ),
					// 	'image_width'  => 300,
					// 	'image_height' => 300
					// ) );

					// $wrapper->subheader(
					// 	__( 'License', 'podlove' )
					// );

					// $podcast = \Podlove\Model\Podcast::get_instance();

					// $wrapper->select( 'license_type', array(
					// 	'label'       => __( 'License', 'podlove' ),
					// 	'options' 	  => array('cc' => 'Creative Commons', 'other' => 'Other'),
					// 	'description' => __( "<p class=\"podlove_podcast_license_status\"></p>", 'podlove' )
					// ) );

					// $wrapper->string( 'license_name', array(
					// 	'label'       => __( 'License Name', 'podlove' )
					// ) );

					// $wrapper->string( 'license_url', array(
					// 	'label'       => __( 'License URL', 'podlove' ),
					// 	'description' => __( 'Example: http://creativecommons.org/licenses/by/3.0/', 'podlove' )
					// ) );

					// $wrapper->select( 'license_cc_allow_modifications', array(
					// 	'label'       => __( 'Modification', 'podlove' ),
					// 	'description' => __( 'Allow modifications of your work?', 'podlove' ),
					// 	'options' => array('yes' => 'Yes', 'yesbutshare' => 'Yes, as long as others share alike', 'no' => 'No')
					// ) );

					// $wrapper->select( 'license_cc_allow_commercial_use', array(
					// 	'label'       => __( 'Commercial Use', 'podlove' ),
					// 	'description' => __( 'Allow commercial uses of your work?', 'podlove' ),
					// 	'options' => array('yes' => 'Yes', 'no' => 'No')
					// ) );

					// $wrapper->select( 'license_cc_license_jurisdiction', array(
					// 	'label'       => __( 'License Jurisdiction', 'podlove' ),
					// 	'options' => \Podlove\License\locales_cc()
					// ) );

					/*
					?>
						<tr class="row_podlove_podcast_license_preview">
							<th scope="row" valign="top">
									<label for="podlove_podcast_subtitle">License Preview</label>
							</th>
							<td>
								<p class="podlove_podcast_license_image"></p>
							</td>
						</tr>
					<?php
					*/

					// $wrapper->subheader(
					// 	__( 'Directory', 'podlove' ),
					// 	__( 'You may provide additional information about your podcast that may or may not be used by podcast directories like iTunes.', 'podlove' )
					// );

					// $wrapper->string( 'author_name', array(
					// 	'label'       => __( 'Author Name', 'podlove' ),
					// 	'description' => __( 'Publicly displayed in Podcast directories.', 'podlove' ),
					// 	'html' => array( 'class' => 'regular-text' )
					// ) );

					// $wrapper->string( 'publisher_name', array(
					// 	'label'       => __( 'Publisher Name', 'podlove' ),
					// 	'description' => __( '', 'podlove' ),
					// 	'html' => array( 'class' => 'regular-text' )
					// ) );

					// $wrapper->string( 'publisher_url', array(
					// 	'label'       => __( 'Publisher URL', 'podlove' ),
					// 	'description' => __( '', 'podlove' ),
					// 	'html' => array( 'class' => 'regular-text' )
					// ) );
			
					// $wrapper->string( 'owner_name', array(
					// 	'label'       => __( 'Owner Name', 'podlove' ),
					// 	'description' => __( 'Used by iTunes and other Podcast directories to contact you.', 'podlove' ),
					// 	'html' => array( 'class' => 'regular-text' )
					// ) );
			
					// $wrapper->string( 'owner_email', array(
					// 	'label'       => __( 'Owner Email', 'podlove' ),
					// 	'description' => __( 'Used by iTunes and other Podcast directories to contact you.', 'podlove' ),
					// 	'html' => array( 'class' => 'regular-text' )
					// ) );
			
					// $wrapper->string( 'keywords', array(
					// 	'label'       => __( 'Keywords', 'podlove' ),
					// 	'description' => __( 'List of keywords. Separate with commas.', 'podlove' ),
					// 	'html' => array( 'class' => 'regular-text' )
					// ) );

					// $wrapper->select( 'category_1', array(
					// 	'label'       => __( 'iTunes Categories', 'podlove' ),
					// 	'description' => '',
					// 	'type'     => 'select',
					// 	'options'  => \Podlove\Itunes\categories()
					// ) );

					// $wrapper->select( 'category_2', array(
					// 	'label'       => '',
					// 	'description' => '',
					// 	'type'     => 'select',
					// 	'options'  => \Podlove\Itunes\categories()
					// ) );

					// $wrapper->select( 'category_3', array(
					// 	'label'       => '',
					// 	'description' => '<br>'
					// 	                 . __( 'For placement within the older, text-based browse system, podcast feeds may list up to 3 category/subcategory pairs. (For example, "Music" counts as 1, as does "Business > Careers.") For placement within the newer browse system based on Category links, however, and for placement within the Top Podcasts and Top Episodes lists that appear in the right column of most podcast pages, only the first category listed in the feed is used.' )
					// 	                 . ' (<a href="http://www.apple.com/itunes/podcasts/specs.html#category" target="_blank">http://www.apple.com/itunes/podcasts/specs.html#category</a>)',
					// 	'options'  => \Podlove\Itunes\categories()
					// ) );

					// $wrapper->select( 'explicit', array(
					// 	'label'       => __( 'Explicit Content?', 'podlove' ),
					// 	'description' => __( '', 'podlove' ),
					// 	'type'    => 'checkbox',
		   //              'options'  => array(0 => 'no', 1 => 'yes', 2 => 'clean')
					// ) );

					// $wrapper->checkbox( 'complete', array(
					// 	'label'       => __( 'Podcast complete?', 'podlove' ),
					// 	'description' => __( 'Shows that this Podcast is finished and no further episodes will be added.', 'podlove' ),
					// 	'default'     => false
					// ) );
					
					do_action( 'podlove_podcast_form', $wrapper, $podcast );

					$wrapper->hidden( 'limit_items', array() );

				});
				?>
			</form>
		</div>	
		<!-- 
		<script type="text/javascript">
			PODLOVE.License({
				plugin_url: "<?php echo \Podlove\PLUGIN_URL; ?>",

				locales: JSON.parse('<?php echo json_encode(\Podlove\License\locales_cc()); ?>'),
				versions: JSON.parse('<?php echo json_encode(\Podlove\License\version_per_country_cc()); ?>'),

				container: '.row_podlove_podcast_license_type',
				type: '<?php echo $podcast->license_type; ?>',
				status: '.podlove_podcast_license_status',
				image: '.podlove_podcast_license_image',
				image_row: 'tr.podlove_podcast_license_image',
				form_row_cc_preview: 'tr.row_podlove_podcast_license_preview',

				form_type: '#podlove_podcast_license_type',
				form_other_name: '#podlove_podcast_license_name',
				form_other_url: '#podlove_podcast_license_url',
				form_cc_commercial_use: '#podlove_podcast_license_cc_allow_commercial_use',
				form_cc_modification: '#podlove_podcast_license_cc_allow_modifications',
				form_cc_jurisdiction: '#podlove_podcast_license_cc_license_jurisdiction',
				form_cc_preview: '#podlove_podcast_license_preview',

				form_row_other_name: 'tr.row_podlove_podcast_license_name',
				form_row_other_url: 'tr.row_podlove_podcast_license_url',
				form_row_cc_commercial_use: 'tr.row_podlove_podcast_license_cc_allow_commercial_use',
				form_row_cc_modification: 'tr.row_podlove_podcast_license_cc_allow_modifications',
				form_row_cc_jurisdiction: 'tr.row_podlove_podcast_license_cc_license_jurisdiction'
			});
		</script>
		-->

		<?php
	}
	
}