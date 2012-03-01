<?php
namespace Podlove;

if( ! class_exists( 'WP_List_Table' ) ){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Feed_List_Table extends \WP_List_Table {
	
	private $is_nested_in_meta_box = false;

	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'feed',   // singular name of the listed records
		    'plural'    => 'feeds',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}

	public function prepare_for_meta_box() {
		$this->is_nested_in_meta_box = true;
	}

	/**
	 * Potentially evil and might backfire as WP_List_Table::display_tablenav
	 * is marked as @protected. But there are no hooks, so I don't have much choice.
	 */
	public function display_tablenav( $which ) {
		if ( ! $this->is_nested_in_meta_box ) {
			parent::display_tablenav( $which );
		}
	}
	
	function column_name( $feed ) {
		$actions = array(
			'edit' => sprintf(
				'<a href="?page=%s&action=%s&show=%s&feed=%s">' . \Podlove\t( 'Edit' ) . '</a>',
				'podlove_shows_settings_handle',
				'edit',
				$feed->show_id,
				$feed->id
			)
		);
	
		return sprintf('%1$s %2$s',
		    /*$1%s*/ $feed->name,
		    /*$3%s*/ $this->row_actions( $actions )
		);
	}
	
	function column_discoverable( $feed ) {
		return $feed->discoverable ? '✓' : '×';
	}

	function column_url( $feed ) {
		return $feed->get_subscribe_link();
	}

	function get_columns(){
		$columns = array(
			'name'        => 'Name',
			'url'         => 'Subscribe URL',
			'discoverable'=> 'Discoverable'
		);
		return $columns;
	}
	
	function prepare_items() {
		// number of items per page
		if ( $this->is_nested_in_meta_box ) {
			$per_page = 10;
		} else {
			// no pagination inside a meta box
			$per_page = 99999;
		}
		
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		// retrieve data
		// TODO select data for current page only
		$data = \Podlove\Model\Feed::all();
		
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
