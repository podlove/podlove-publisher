<?php
namespace Podlove\Modules\Contributors\Settings\Tab\Contributors;
use \Podlove\Modules\Contributors\Settings\Tab;
use \Podlove\Modules\Contributors\Model\Contributor;

class General extends Tab {

	public function form_template($form_attributes, $action, $contributor) {
		\Podlove\Form\build_for( $contributor, $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$contributor = $form->object;

			$wrapper->string( 'realname', array(
				'label'       => __( 'Real name', 'podlove' ),
				'html'        => array( 'class' => 'podlove-check-input required podlove-contributor-field' )
			) );

			$wrapper->string( 'publicname', array(
				'label'       => __( 'Public name', 'podlove' ),
				'description' => 'The Public Name will be used for public mentions. E.g. the Web Player. If left blank, it defaults to the "real name".',
				'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
			) );

			$wrapper->string( 'nickname', array(
				'label'       => __( 'Nickname', 'podlove' ),
				'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
			) );

			$wrapper->select( 'gender', array(
				'label'       => __( 'Gender', 'podlove' ),
				'options'     => array( 'female' => 'Female', 'male' => 'Male', 'none' => 'Not attributed')
			) );
			
			$wrapper->string( 'privateemail', array(
				'label'       => __( 'Contact email', 'podlove' ),
				'description' => 'The provided email will be used for internal purposes only.',
				'html'        => array( 'class' => 'podlove-contributor-field podlove-check-input', 'data-podlove-input-type' => 'email' )
			) );
			
			$wrapper->avatar( 'avatar', array(
				'label'       => __( 'Avatar', 'podlove' ),
				'description' => 'Either a Gravatar email adress or a URL.',
				'html'        => array( 'class' => 'podlove-contributor-field podlove-check-input', 'data-podlove-input-type' => 'avatar' )
			) );

			$wrapper->string( 'slug', array(
				'label'       => __( 'ID', 'podlove' ),
				'description' => 'The ID will be used as in internal identifier for e.g. shortcodes.',
				'html'        => array( 'class' => 'podlove-check-input required podlove-contributor-field' )
			) );

			$wrapper->string( 'guid', array(
				'label'       => __( 'URI', 'podlove' ),
				'description' => __('An URI acts as a globally unique ID to identify contributors across podcasts on the internet.', 'podlove'),
				'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
			) );		

			$wrapper->radio( 'visibility', array(
				'label'       => __( 'Visibility', 'podlove' ),
				'options'	  => array( '1' => 'Yes, the contributor’s information will be visible for the public (e.g. displayed in the Contributor Table).<br />', 
					                    '0' => 'No, the contributor’s information will be private and not visible for anybody.' ),
				'default'	  => '1'
			) );
		});
	}
}