<?php
namespace Podlove\Settings\Podcast\Tab;
use \Podlove\Settings\Podcast\Tab;

class Description extends Tab {

	public function init() {
		add_action( $this->page_hook, array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public function process_form() {
		if (!isset($_POST['podlove_podcast']) || !$this->is_active())
			return;

		$formKeys = array('title', 'subtitle', 'summary', 'language');

		$settings = get_option('podlove_podcast');
		foreach ($formKeys as $key) {
			$settings[$key] = $_POST['podlove_podcast'][$key];
		}
		update_option('podlove_podcast', $settings);
		header('Location: ' . $this->get_url());
	}

	public function register_page() {
		$podcast = \Podlove\Model\Podcast::get_instance();
		
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

			$wrapper->select( 'language', array(
				'label'       => __( 'Language', 'podlove' ),
				'description' => '',
				'default'     => get_bloginfo( 'language' ),
				'options'  => \Podlove\Locale\locales()
			) );
		});
	}
}
