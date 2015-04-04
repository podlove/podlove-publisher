<?php
namespace Podlove\Modules\Social\Settings\Tab;
use \Podlove\Modules\Contributors\Settings\Tab;
use \Podlove\Modules\Contributors\Model\Contributor;

class Donation extends Tab {

	public function form_template($form_attributes, $action, $contributor) {
		\Podlove\Form\build_for( $contributor, $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$contributor = $form->object;

			$wrapper->callback( 'services_form_table', array(
				'nolabel' => true,
				'callback' => function() {

					if (isset($_GET['contributor'])) {
						$services = \Podlove\Modules\Social\Model\ContributorService::find_by_contributor_id_and_category( $_GET['contributor'], 'donation' );
					} else {
						$services = array();
					}

					\Podlove\Modules\Social\Social::services_form_table( $services, 'podlove_contributor[donations]', 'donation' );
				}
			) );
		});
	}
	
}