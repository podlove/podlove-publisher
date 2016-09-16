<?php
namespace Podlove;

class File_Type_List_Table extends \Podlove\List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'file_type',   // singular name of the listed records
		    'plural'    => 'file_types',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}
	
	function column_name( $file_type ) {
		$actions = array(
			'edit' => sprintf(
				'<a href="?page=%s&podlove_tab=%s&action=%s&file_type=%s">' . __( 'Edit', 'podlove-podcasting-plugin-for-wordpress' ) . '</a>',
				filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING),
				filter_input(INPUT_GET, 'podlove_tab', FILTER_SANITIZE_STRING),
				'edit',
				$file_type->id
			),
			'delete' => sprintf(
				'<a href="?page=%s&podlove_tab=%s&action=%s&file_type=%s">' . __( 'Delete', 'podlove-podcasting-plugin-for-wordpress' ) . '</a>',
				filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING),
				filter_input(INPUT_GET, 'podlove_tab', FILTER_SANITIZE_STRING),
				'delete',
				$file_type->id
			)
		);
	
		return sprintf('%1$s %2$s',
		    /*$1%s*/ $file_type->name,
		    /*$3%s*/ $this->row_actions( $actions )
		);
	}
	
	function column_id( $file_type ) {
		return $file_type->id;
	}
	
	function column_file_type( $file_type ) {
		return $file_type->type;
	}
	
	function column_mime( $file_type ) {
		return $file_type->mime_type;
	}
	
	function column_extension( $file_type ) {
		return $file_type->extension;
	}

	function get_columns(){
		return array(
			'id'        => __( 'ID', 'podlove-podcasting-plugin-for-wordpress' ),
			'name'      => __( 'Name', 'podlove-podcasting-plugin-for-wordpress' ),
			'file_type' => __( 'File Type', 'podlove-podcasting-plugin-for-wordpress' ),
			'mime'      => __( 'MIME Type', 'podlove-podcasting-plugin-for-wordpress' ),
			'extension' => __( 'Extension', 'podlove-podcasting-plugin-for-wordpress' )
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
		$data = \Podlove\Model\FileType::all();
		
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
