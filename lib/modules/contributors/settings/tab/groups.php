<?php 
namespace Podlove\Modules\Contributors\Settings\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class Groups extends Tab {

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
			'group',
			'\Podlove\Modules\Contributors\Model\ContributorGroup'
		);

		$this->page->set_form(function($form_args, $group, $action) {

			\Podlove\Form\build_for( $group, $form_args, function ( $form ) {
				$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

				$wrapper->string( 'title', array(
					'label' => __( 'Group Title', 'podlove-podcasting-plugin-for-wordpress' ),
					'html'  => array( 'class' => 'required' )
				) );

				$wrapper->string( 'slug', array(
					'label' => __( 'Group Slug', 'podlove-podcasting-plugin-for-wordpress' ),
					'html'  => array( 'class' => 'required' )
				) );

			} );

		});

		$this->page->enable_tabs('groups');
		$this->page->set_labels(array(
			'delete_confirm' => __( 'You selected to delete the group "%s". Please confirm this action.', 'podlove-podcasting-plugin-for-wordpress' ),
			'add_new' => __( 'Add new group', 'podlove-podcasting-plugin-for-wordpress' ),
			'edit' => __( 'Edit group', 'podlove-podcasting-plugin-for-wordpress' )
		));

		add_action('podlove_settings_group_view', function() {
			echo sprintf(
				__('Use groups to divide contributors by type of participation. Create a group for teams working together or for a supporting community. Team members can be displayed separately by using the %sappropriate option%s to select a group.', 'podlove-podcasting-plugin-for-wordpress'),
				'<a href="http://docs.podlove.org/ref/template-tags.html#contributors" target="_blank">',
				'</a>'
			);
			$table = new \Podlove\Modules\Contributors\Contributor_Group_List_Table();
			$table->prepare_items();
			$table->display();
		});
	}

}