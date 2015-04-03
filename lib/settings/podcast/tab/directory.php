<?php
namespace Podlove\Settings\Podcast\Tab;
use \Podlove\Settings\Podcast\Tab;

class Directory extends Tab {

	public function init() {
		add_action( $this->page_hook, array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public function process_form() {
		if (!isset($_POST['podlove_podcast']) || !$this->is_active())
			return;

		$formKeys = array(
			'author_name',
			'publisher_name',
			'publisher_url',
			'owner_name',
			'owner_email',
			'keywords',
			'category_1',
			'category_2',
			'category_3',
			'explicit',
			'complete',
		);

		$settings = get_option('podlove_podcast');
		foreach ($formKeys as $key) {
			$settings[$key] = stripslashes($_POST['podlove_podcast'][$key]);
		}
		update_option('podlove_podcast', $settings);
		header('Location: ' . $this->get_url());
	}

	public function register_page() {
		$podcast = \Podlove\Model\Podcast::get();
		
		$form_attributes = array(
			'context' => 'podlove_podcast',
			'action'  => $this->get_url()
		);

		?>
		<p>
			<?php echo __( 'You may provide additional information about your podcast that may or may not be used by podcast directories like iTunes.', 'podlove' ); ?>
		</p>
		<?php

		\Podlove\Form\build_for( $podcast, $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$podcast = $form->object;

			$wrapper->string( 'author_name', array(
				'label'       => __( 'Author Name', 'podlove' ),
				'description' => __( 'Publicly displayed in Podcast directories.', 'podlove' ),
				'html' => array( 'class' => 'regular-text podlove-check-input' )
			) );

			$wrapper->string( 'publisher_name', array(
				'label'       => __( 'Publisher Name', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'html' => array( 'class' => 'regular-text podlove-check-input' )
			) );

			$wrapper->string( 'publisher_url', array(
				'label'       => __( 'Publisher URL', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'html' => array( 'class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'url' )
			) );
	
			$wrapper->string( 'owner_name', array(
				'label'       => __( 'Owner Name', 'podlove' ),
				'description' => __( 'Used by iTunes and other Podcast directories to contact you.', 'podlove' ),
				'html' => array( 'class' => 'regular-text podlove-check-input' )
			) );
	
			$wrapper->string( 'owner_email', array(
				'label'       => __( 'Owner Email', 'podlove' ),
				'description' => __( 'Used by iTunes and other Podcast directories to contact you.', 'podlove' ),
				'html' => array( 'class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'email' )
			) );
	
			$wrapper->string( 'keywords', array(
				'label'       => __( 'Keywords', 'podlove' ),
				'description' => __( 'List of keywords. Separate with commas.', 'podlove' ),
				'html' => array( 'class' => 'regular-text podlove-check-input' )
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