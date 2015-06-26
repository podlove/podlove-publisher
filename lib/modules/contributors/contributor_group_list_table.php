<?php
namespace Podlove\Modules\Contributors;

class Contributor_Group_List_Table extends \Podlove\List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'contributor group',   // singular name of the listed records
		    'plural'    => 'contributor groupes',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}
	

	public function column_title( $group ) {
		$actions = array(
			'edit'   => Settings\GenericEntitySettings::get_action_link( 'group', $group->id, __( 'Edit', 'podlove' ) ),
			'delete' => Settings\GenericEntitySettings::get_action_link( 'group', $group->id, __( 'Delete', 'podlove' ), 'confirm_delete' )
		);
	
		return sprintf( '%1$s %2$s',
		    Settings\GenericEntitySettings::get_action_link( 'group', $group->id, $group->title ),
		    $this->row_actions( $actions )
		) . '<input type="hidden" class="group_id" value="' . $group->id . '">';;
	}

	public function column_slug( $role ) {
		return $role->slug;
	}

	public function get_columns(){
		$columns = array(
			'title' => __( 'Group Title', 'podlove' ),
			'slug'  => __( 'Group Slug', 'podlove' ),
		);
		return $columns;
	}
	
	public function prepare_items() {
		// number of items per page
		$per_page = 10;
		
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		// retrieve data
		$data = \Podlove\Modules\Contributors\Model\ContributorGroup::all( 'ORDER BY title ASC' );
		
		// get current page
		$current_page = $this->get_pagenum();
		// get total items
		$total_items = count( $data );
		// extrage page for current page only
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ) , $per_page );
		// add items to table
		$this->items = $data;
		
		// register pagination options & calculations
		$this->set_pagination_args( array(
		    'total_items' => $total_items,
		    'per_page'    => $per_page,
		    'total_pages' => ceil( $total_items / $per_page )
		) );
	}


}
