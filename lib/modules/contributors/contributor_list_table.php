<?php
namespace Podlove\Modules\Contributors;

class Contributor_List_Table extends \Podlove\List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'contributor',   // singular name of the listed records
		    'plural'    => 'contributors',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}
	

	public function column_realname( $contributor ) {
		$actions = array(
			'edit'   => Settings\Contributors::get_action_link( $contributor, __( 'Edit', 'podlove' ) ),
			'delete' => Settings\Contributors::get_action_link( $contributor, __( 'Delete', 'podlove' ), 'confirm_delete' )
		);
	
		return sprintf( '%1$s %2$s',
		    Settings\Contributors::get_action_link( $contributor, $contributor->realname ),
		    $this->row_actions( $actions )
		) . '<input type="hidden" class="contributor_id" value="' . $contributor->id . '">';;
	}

	public function column_publicname( $contributor ) {
		return $contributor->publicname;
	}

	public function column_slug( $contributor ) {
		return $contributor->slug;
	}
	
	public function column_role( $contributor ) {
		switch($contributor->role) {
			case "moderator" :
				return "Moderator";
			break;
			case "comoderator" :
				return "Co-Moderator";
			break;
			case "camera" :
				return "Camera";
			break;
			case "chatmoderator" :
				return "Chat-Moderator";
			break;		
			case "shownoter" :
				return "Shownoter";
			break;			
			case "guest" :
				return "Guest";
			break;
		}
	}
	
	public function column_privateemail( $contributor ) {
		return "<a href='mailto:".$contributor->privateemail."'>".$contributor->privateemail."</a>";
	}
	
	public function column_showpublic( $contributor ) {
		return $contributor->showpublic ? '✓' : '×';
	}

	public function column_permanentcontributor( $contributor ) {
		return $contributor->permanentcontributor ? '✓' : '×';
	}

	public function get_columns(){
		$columns = array(
			'realname'         => __( 'Real Name', 'podlove' ),
			'publicname'         => __( 'Public Name', 'podlove' ),
			'role'         	=> __( 'Role', 'podlove' ),
			'slug'         	=> __( 'ID', 'podlove' ),
			'privateemail'         	=> __( 'Private E-mail', 'podlove' ),
			'permanentcontributor'	=> __( 'Permanent Contributor', 'podlove' ),
			'showpublic'         	=> __( 'Public Profile?', 'podlove' )
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
		$data = \Podlove\Modules\Contributors\Contributor::all( 'ORDER BY realname ASC' );
		
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
