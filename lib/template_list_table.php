<?php
namespace Podlove;

class Template_List_Table extends \Podlove\List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'episode_template',   // singular name of the listed records
		    'plural'    => 'episode_templates',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}
	
	function column_name( $template ) {

		$actions = array(
			'edit'   => Settings\Templates::get_action_link( $template, __( 'Edit', 'podlove' ), 'edit' ),
			'delete' => Settings\Templates::get_action_link( $template, __( 'Delete', 'podlove' ), 'confirm_delete' )
		);
	
		return sprintf('%1$s %2$s',
		    /*$1%s*/ $template->title . '<br><code>[podlove-template id="' . $template->title . '"]</code>',
		    /*$3%s*/ $this->row_actions( $actions )
		);
	}
	
	function column_id( $template ) {
		return $template->id;
	}
	
	function column_content( $template ) {
		return "<textarea class='highlight-readonly'>$template->content</textarea>";
	}

	function get_columns(){
		return array(
			'name'    => __( 'Title & Shortcode', 'podlove' ),
			'content' => __( 'Content', 'podlove' )
		);
	}
	
	function prepare_items() {
		// number of items per page
		$per_page = 1000;
		
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		// retrieve data
		// TODO select data for current page only
		$data = \Podlove\Model\Template::find_all_by_where('readonly=0');
		
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
