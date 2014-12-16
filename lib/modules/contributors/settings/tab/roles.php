<?php 
namespace Podlove\Modules\Contributors\Settings\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class Roles extends Tab {

	private $page = NULL;

	public function init() {
		$this->page_type = 'custom';
		add_action( 'podlove_expert_settings_page', array( $this, 'register_page' ) );
	}

	public function register_page() {
		$this->page = $this->getObject();
		$this->page->page();
	}

	public function getObject() {
		
		if (!$this->page)
			$this->createObject();

		return $this->page;
	}

	public function createObject() {
		$this->page = new \Podlove\Modules\Contributors\Settings\GenericEntitySettings(
			'role',
			'\Podlove\Modules\Contributors\Model\ContributorRole'
		);

		$this->page->set_form(function($form_args, $role, $action) {

			\Podlove\Form\build_for( $role, $form_args, function ( $form ) {
				$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

				$wrapper->string( 'title', array(
					'label' => __( 'Role Title', 'podlove' ),
					'html'  => array( 'class' => 'required' )
				) );

				$wrapper->string( 'slug', array(
					'label' => __( 'Role Slug', 'podlove' ),
					'html'  => array( 'class' => 'required' )
				) );

			} );

		});

		$this->page->enable_tabs('roles');
		$this->page->set_labels(array(
			'delete_confirm' => __( 'You selected to delete the role "%s". Please confirm this action.', 'podlove' ),
			'add_new' => __( 'Add new role', 'podlove' ),
			'edit' => __( 'Edit role', 'podlove' )
		));

		add_action('podlove_settings_role_view', function() {
			echo __('Use roles to assign a certain type of activity to a single contributor independent of any assigned group. A role might be helpful to mark somebody as being the main presenter of a show or a guest. Use roles sparingly as most of the times, groups might the more valuable way to structure contributors.', 'podlove');
			$table = new \Podlove\Modules\Contributors\Contributor_Role_List_Table();
			$table->prepare_items();
			$table->display();
		});
	}
}