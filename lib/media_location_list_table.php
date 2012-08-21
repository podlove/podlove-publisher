<?php
namespace Podlove;

if( ! class_exists( 'WP_List_Table' ) ){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Media_Location_List_Table extends \WP_List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'media_location',   // singular name of the listed records
		    'plural'    => 'media_locations',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}
	
	public function column_title( $media_location ) {

		$link = function ( $title, $action = 'edit' ) use ( $media_location ) {
			return sprintf(
				'<a href="?page=%s&action=%s&media_location=%s">' . $title . '</a>',
				$_REQUEST['page'],
				$action,
				$media_location->id
			);
		};

		$actions = array(
			'edit'   => $link( __( 'Edit', 'podlove' ) ),
			'delete' => $link( __( 'Delete', 'podlove' ), 'delete' )
		);
	
		$title = ( $media_location->title ) ? $media_location->title : __( '- title missing -', 'podlove' );

		return sprintf( '%1$s %2$s',
		    $link( $title ),
		    $this->row_actions( $actions )
		);
	}
	
	public function column_media_format( $media_location ) {
		$format = $media_location->media_format();
		return ( $format ) ? $format->title() : "-";
	}

	public function get_columns(){
		$columns = array(
			'title'        => __( 'Media Location', 'podlove' ),
			'media_format' => __( 'Media Format', 'podlove' ),
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
		$data = \Podlove\Model\MediaLocation::all();
		
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
