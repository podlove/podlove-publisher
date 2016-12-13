<?php
namespace Podlove\Settings\Podcast\Tab;
use \Podlove\Settings\Podcast\Tab;

class Description extends Tab {

	public function init() {
		add_action( $this->page_hook, array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public function process_form() {

		if (!isset($_GET['page']) || $_GET['page'] !== 'podlove_settings_podcast_handle')
			return;

		if (!isset($_POST['podlove_podcast']) || !$this->is_active())
			return;

		$formKeys = array('title', 'subtitle', 'summary', 'language', 'cover_image');

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
			<?php echo __( 'These are the three most important fields describing your podcast.
					<strong>Title</strong> is the title of the podcast that is the primary field to be used to represent the podcast in directories, lists and other uses.
					The <strong>subtitle</strong> is an extension to the title. The subtitle is meant to clarify what the podcast is about. While a title can be anything, a subtitle should be more descriptive in what the content actually wants to convey and what the most important information is, you want everybody want to know about the offering.
					A <strong>summary</strong> is a much more precise and elaborate description of the podcast\'s content. While title and subtitle are rather concise, a summary is meant to consist of one or more sentences that form a paragraph or more.', 'podlove' ) ?>
		</p>
		<?php

		\Podlove\Form\build_for( $podcast, $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$podcast = $form->object;

			$wrapper->string( 'title', array(
				'label'       => __( 'Title', 'podlove-podcasting-plugin-for-wordpress' ),
				'html'        => array( 'class' => 'regular-text required podlove-check-input' )
			) );

			$wrapper->string( 'subtitle', array(
				'label'       => __( 'Subtitle', 'podlove-podcasting-plugin-for-wordpress' ),
				'description' => __( 'Extension to the title. Clarify what the podcast is about.', 'podlove-podcasting-plugin-for-wordpress' ),
				'html'        => array( 'class' => 'regular-text podlove-check-input' )
			) );

			$wrapper->text( 'summary', array(
				'label'       => __( 'Summary', 'podlove-podcasting-plugin-for-wordpress' ),
				'description' => __( 'Elaborate description of the podcast\'s content.', 'podlove-podcasting-plugin-for-wordpress' ),
				'html'        => array( 'rows' => 3, 'cols' => 40, 'class' => 'autogrow podlove-check-input' )
			) );

			$wrapper->upload( 'cover_image', array(
				'label'        => __( 'Image URL', 'podlove-podcasting-plugin-for-wordpress' ),
				'description'  => __( 'Apple/iTunes recommends 3000 x 3000 pixel JPG or PNG.', 'podlove-podcasting-plugin-for-wordpress' ),
				'html'         => array( 'class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'url'  ),
				'media_button_text' => __("Use for Podcast Image", 'podlove-podcasting-plugin-for-wordpress')
			) );

			$wrapper->select( 'language', array(
				'label'       => __( 'Language', 'podlove-podcasting-plugin-for-wordpress' ),
				'description' => __( '', 'podlove-podcasting-plugin-for-wordpress' ),
				'default'     => get_bloginfo( 'language' ),
				'options'  => \Podlove\Locale\locales()
			) );
		});
	}
}
