<?php 
namespace Podlove\Modules\Contributors\Settings\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;
use \Podlove\Modules\Contributors\Contributor_List_Table;

class Contributors extends Tab {

	private $page = NULL;

	public function get_slug() {
		return 'contributors';
	}

	public function init() {
		$this->page_type = 'custom';
		add_action( 'podlove_expert_settings_page', array( $this, 'register_page' ) );
		add_action( "load-" . \Podlove\Modules\Contributors\Settings\ContributorSettings::$pagehook,  array( $this, 'add_contributors_screen_options' ) );
	}

	public function add_contributors_screen_options() {
		add_screen_option( 'per_page', array(
	       'label'   => __( 'Contributors', 'podlove-podcasting-plugin-for-wordpress' ),
	       'default' => 10,
	       'option'  => 'podlove_contributors_per_page'
		) );

		$this->table = new Contributor_List_Table();
	}

	public function register_page() {
		$this->object = $this->getObject();
		$this->object->page();
	}

	public function getObject() {
		
		if (!$this->page)
			$this->createObject();

		return $this->page;
	}

	public function createObject() {
		$this->page = new \Podlove\Modules\Contributors\Settings\GenericEntitySettings(
			'contributor',
			'\Podlove\Modules\Contributors\Model\Contributor'
		);

		$this->page->enable_tabs('contributors');

		$this->page->set_form(function($form_args, $contributor, $action) {
			$this->contributor_form($form_args, $contributor, $action);
		});

		add_action('podlove_settings_contributor_view', function() {
			$this->table->prepare_items();
			$this->table->display();
		});

		add_filter('podlove_generic_entity_attributes_contributor', function ($attributes) {

			$sanitize = function ($var) {
				return filter_var(stripslashes($var), FILTER_SANITIZE_STRING, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES ]);
			};

			$attributes['publicname']     = $sanitize($attributes['publicname']);
			$attributes['realname']       = $sanitize($attributes['realname']);
			$attributes['nickname']       = $sanitize($attributes['nickname']);
			$attributes['organisation']   = $sanitize($attributes['organisation']);
			$attributes['department']     = $sanitize($attributes['department']);
			$attributes['jobtitle']       = $sanitize($attributes['jobtitle']);

			return $attributes;
		});
	}

	private function contributor_form($form_args, $contributor, $action) {

		$general_fields = [
			'realname' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'Real name', 'podlove-podcasting-plugin-for-wordpress' ),
					'html'        => array( 'class' => 'podlove-check-input required podlove-contributor-field' )
				)
			], 
			'publicname' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'Public name', 'podlove-podcasting-plugin-for-wordpress' ),
					'description' => __('The Public Name will be used for public mentions.', 'podlove-podcasting-plugin-for-wordpress' ),
					'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				)
			], 
			'nickname' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'Nickname', 'podlove-podcasting-plugin-for-wordpress' ),
					'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				)
			],
			'gender' => [
				'field_type' => 'select',
				'field_options' => array(
					'label'       => __( 'Gender', 'podlove-podcasting-plugin-for-wordpress' ),
					'options'     => array( 'female' => 'Female', 'male' => 'Male', 'none' => 'Not attributed')
				)
			], 
			'privateemail' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'Contact email', 'podlove-podcasting-plugin-for-wordpress' ),
					'description' => __('The provided email will be used for internal purposes only.', 'podlove-podcasting-plugin-for-wordpress' ),
					'html'        => array( 'class' => 'podlove-contributor-field podlove-check-input', 'data-podlove-input-type' => 'email' )
				)
			],
			'avatar' => [
				'field_type' => 'upload',
				'field_options' => array(
					'label'       => __( 'Avatar', 'podlove-podcasting-plugin-for-wordpress' ),
					'description' => __('Either a Gravatar email adress or a URL.', 'podlove-podcasting-plugin-for-wordpress' ),
					'html'        => array( 
						'class' => 'podlove-contributor-field podlove-check-input', 
						'data-podlove-input-type' => 'avatar' 
					),
					'allow_gravatar' => true
				)
			], 
			'identifier' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'ID', 'podlove-podcasting-plugin-for-wordpress' ),
					'description' => __('The ID will be used as in internal identifier for e.g. shortcodes.', 'podlove-podcasting-plugin-for-wordpress' ),
					'html'        => array( 'class' => 'podlove-check-input required podlove-contributor-field' )
				)
			], 
			'guid' => [
				'field_type' => 'string',
				'field_options' => array(
					'label'       => __( 'URI', 'podlove-podcasting-plugin-for-wordpress' ),
					'description' => __('An URI acts as a globally unique ID to identify contributors across podcasts on the internet.', 'podlove-podcasting-plugin-for-wordpress'),
					'html'        => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				)
			], 
			'visibility' => [
				'field_type' => 'radio',
				'field_options' => array(
					'label'       => __( 'Visibility', 'podlove-podcasting-plugin-for-wordpress' ),
					'options'	  => array( '1' => __('Yes, the contributor’s information will be visible for the public (e.g. displayed in the Contributor Table).<br />', 'podlove-podcasting-plugin-for-wordpress' ), 
						                    '0' => __('No, the contributor’s information will be private and not visible for anybody.', 'podlove-podcasting-plugin-for-wordpress' ) ),
					'default'	  => '1'
				)
			]
		];

		$general_fields = apply_filters('podlove_contributors_general_fields', $general_fields);

		$affiliation_fields = [
			'organisation' => [
				'field_type' => 'string',
				'field_options' => [
					'label' => __( 'Organisation', 'podlove-podcasting-plugin-for-wordpress' ),
					'html'  => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				]
			],
			'department' => [
				'field_type' => 'string',
				'field_options' => [
					'label' => __( 'Department', 'podlove-podcasting-plugin-for-wordpress' ),
					'html'  => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				]
			],
			'jobtitle' => [
				'field_type' => 'string',
				'field_options' => [
					'label' => __( 'Job Title', 'podlove-podcasting-plugin-for-wordpress' ),
					'html'  => array( 'class' => 'podlove-check-input podlove-contributor-field' )
				]
			],
		];

		$affiliation_fields = apply_filters('podlove_contributors_affiliation_fields', $affiliation_fields);

		$form_sections = [
			'general' => [
				'title'  => __('General', 'podlove-podcasting-plugin-for-wordpress'),
				'fields' => $general_fields
			],
			'affiliation' => [
				'title'  => __('Affiliation', 'podlove-podcasting-plugin-for-wordpress'),
				'fields' => $affiliation_fields
			]
		];

		$form_sections = apply_filters('podlove_contributor_settings_sections', $form_sections);

		if ( $_GET["action"] !== 'new' )
			$contributor = \Podlove\Modules\Contributors\Model\Contributor::find_by_id( $_REQUEST['contributor'] );

		switch ( $_GET["action"] ) {
			case 'new':   $action = 'create';  break;
			case 'edit':  $action = 'save'; break;
			default:      $action = 'delete'; break;
		}

		\Podlove\Form\build_for( $contributor, $form_args, function ( $form ) use ($form_sections) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$contributor = $form->object;

			foreach ($form_sections as $form_section) {
				$wrapper->subheader($form_section['title']);
				foreach ($form_section['fields'] as $field_name => $field) {
					call_user_func_array([$wrapper, $field['field_type']], [$field_name, $field['field_options']]);
				}
			}

		});
	}
}
