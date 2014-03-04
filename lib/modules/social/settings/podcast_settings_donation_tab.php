<?php 
namespace Podlove\Modules\Social\Settings;

use \Podlove\Settings\Podcast\Tab;
use \Podlove\Modules\Social\Model\ShowService;

class PodcastSettingsDonationTab extends Tab {

	public function init() {
		add_action( $this->page_hook, array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public function process_form() {
		if (!isset($_POST['podlove_podcast']) || !$this->is_active())
			return;

		$formKeys = array('donations');

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
			'action'  => $this->get_url(),
			'is_table' => false
		);

		?>
		<p>
			<?php echo sprintf(
				__( 'These are the possibilities to donate for your Podcast. Display this list using the shortcode %s', 'podlove' ),
				'<code>[podlove-podcast-donations-list]</code>'
			); ?>
		</p>
		<?php

		\Podlove\Form\build_for( $podcast, $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\DivWrapper( $form );
			$podcast = $form->object;

	    	$wrapper->callback( 'services', array(
				// 'label'    => __( 'Contributors', 'podlove' ),
				'callback' => array( __CLASS__, 'podcast_form_extension_form' )
			) );
		});
	}

	public static function podcast_form_extension_form()
	{
		$services = \Podlove\Modules\Social\Model\ShowService::find_by_type( 'donation' );
		\Podlove\Modules\Social\Social::services_form_table($services, 'podlove_podcast[donations]', 'donation');
	}
}