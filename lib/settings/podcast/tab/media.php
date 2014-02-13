<?php
namespace Podlove\Settings\Podcast\Tab;
use \Podlove\Settings\Podcast\Tab;

class Media extends Tab {

	public function init() {
		add_action( $this->page_hook, array( $this, 'register_page' ) );
	}

	public function register_page() {
		$podcast = \Podlove\Model\Podcast::get_instance();
		
		$form_attributes = array(
			'context' => 'podlove_podcast',
			'action'  => $this->get_url()
		);

		?>
		<p>
			<?php echo __( 'The Podlove Publisher expects all your media files to be in the same <strong>Upload Location</strong>.
					It should be a publicly readable directory containing all media files.
					You should not create a separate directory for each episode.', 'podlove' ); ?>
		</p>
		<?php

		\Podlove\Form\build_for( $podcast, $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$podcast = $form->object;

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
		});
	}
}