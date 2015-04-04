<?php
namespace Podlove\Modules\Contributors\Settings\Tab\Contributors;
use \Podlove\Modules\Contributors\Settings\Tab;
use \Podlove\Modules\Contributors\Model\Contributor;

class Affiliation extends Tab {

	public function form_template($form_attributes, $action, $contributor) {
		\Podlove\Form\build_for( $contributor, $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$contributor = $form->object;

			$wrapper->string( 'organisation', array(
				'label'       => __( 'Organisation', 'podlove' ),
				'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
			) );
			
			$wrapper->string( 'department', array(
				'label'       => __( 'Department', 'podlove' ),
				'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
			) );

			$wrapper->string( 'jobtitle', array(
				'label'       => __( 'Job Title', 'podlove' ),
				'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
			) );
		});
	}
}