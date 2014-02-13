<?php 
namespace Podlove\Modules\Contributors\Settings;

use \Podlove\Settings\Podcast\Tab;
use \Podlove\Modules\Contributors\Model\ShowContribution;

class PodcastSettingsTab extends Tab {

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
				__( 'Contributors', 'podlove' ),
				__( 'You may define contributors for the whole podcast.', 'podlove' )
			);

	    	$wrapper->callback( 'contributors', array(
				'label'    => __( 'Contributors', 'podlove' ),
				'callback' => array( __CLASS__, 'podcast_form_extension_form' )
			) );
		});
	}

	public static function podcast_form_extension_form()
	{
		$contributions = ShowContribution::all();
		\Podlove\Modules\Contributors\Contributors::contributors_form_table($contributions, 'podlove_podcast[contributor]');
	}
}