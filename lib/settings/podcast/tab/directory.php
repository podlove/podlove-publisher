<?php
namespace Podlove\Settings\Podcast\Tab;
use \Podlove\Settings\Podcast\Tab;

class Directory extends Tab {

	public function init() {
		add_action( $this->page_hook, array( $this, 'register_page' ) );
	}

	public function register_page() {
		$podcast = \Podlove\Model\Podcast::get_instance();
		
		$form_attributes = array(
			'context' => 'podlove_podcast',
			'action'  => $this->get_url()
		);

		\Podlove\Form\build_for( $podcast, $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$podcast = $form->object;
			
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

			$wrapper->select( 'explicit', array(
				'label'       => __( 'Explicit Content?', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'type'    => 'checkbox',
                'options'  => array(0 => 'no', 1 => 'yes', 2 => 'clean')
			) );

			$wrapper->checkbox( 'complete', array(
				'label'       => __( 'Podcast complete?', 'podlove' ),
				'description' => __( 'Shows that this Podcast is finished and no further episodes will be added.', 'podlove' ),
				'default'     => false
			) );
		});
	}
}