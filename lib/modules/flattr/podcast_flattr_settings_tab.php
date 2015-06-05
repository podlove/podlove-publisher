<?php 
namespace Podlove\Modules\Flattr;

use \Podlove\Settings\Podcast\Tab;

class PodcastFlattrSettingsTab extends Tab {

	public function init() {
		add_action( $this->page_hook, array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public function process_form() {
		if (!isset($_POST['podlove_flattr']) || !$this->is_active())
			return;

		update_option('podlove_flattr', [
			'account'                       => $_POST['podlove_flattr']['account'],
			'contributor_shortcode_default' => (bool) $_POST['podlove_flattr']['contributor_shortcode_default']
		]);

		header('Location: ' . $this->get_url());
	}

	public function register_page() {
		$podcast = \Podlove\Model\Podcast::get();
		
		$form_attributes = array(
			'context' => 'podlove_flattr',
			'action'  => $this->get_url()
		);

		?>
		<p>
			<?php echo __('This Flattr account will be associated with your Podcast. Flattr donations for e.g. new episodes
							will be linked with this account.', 'podlove') ?>
		</p>
		<style type="text/css">
		/* add linebreak after each radio button+label */
		input[type="radio"] + label::after {
		  content: " ";
		  display: block;
		}
		</style>
		<?php

		\Podlove\Form\build_for( (object) get_option('podlove_flattr', []), $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$podcast = $form->object;

			$wrapper->string('account', [
				'label' => __('Flattr Account', 'podlove'),
				'html'  => ['class' => 'regular-text required podlove-check-input']
			]);

			$wrapper->radio('contributor_shortcode_default', [
				'label'       => __('Default Parameter in Contributors Shortcodes', 'podlove'),
				'description' => '<br>' . __('You can override this setting individually by passing along the <code>flattr="yes"</code> or <code>flattr="no"</code> parameter to the shortcodes.', 'podlove'),
				'options'     => [
					'yes' => 'yes, show Flattr buttons by default', 
					'no'  => 'no, do not show Flattr buttons by default'
				],
				'default'     => 'no'
			]);
		});
	}
}
