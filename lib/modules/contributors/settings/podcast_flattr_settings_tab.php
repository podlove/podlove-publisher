<?php 
namespace Podlove\Modules\Contributors\Settings;

use \Podlove\Settings\Podcast\Tab;
use \Podlove\Modules\Contributors\Model\ShowContribution;

class PodcastFlattrSettingsTab extends Tab {

	public function init() {
		add_action( $this->page_hook, array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public function process_form() {
		if (!isset($_POST['podlove_podcast']) || !$this->is_active())
			return;

		$formKeys = array('flattr');

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
			<?php echo __( 'This Flattr account will be associated with your Podcast. Flattr donations for e.g. new episodes
							will be linked with this account.', 'podlove' ) ?>
		</p>
		<?php

		\Podlove\Form\build_for( $podcast, $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$podcast = $form->object;

			$wrapper->string( 'flattr', array(
				'label'       => __( 'Flattr Account', 'podlove' ),
				'html'        => array( 'class' => 'regular-text required' )
			) );
		});
	}
}