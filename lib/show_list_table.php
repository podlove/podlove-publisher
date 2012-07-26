<?php
namespace Podlove;

if( ! class_exists( 'WP_List_Table' ) ){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Show_List_Table extends \WP_List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'show',   // singular name of the listed records
		    'plural'    => 'shows',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}

	function column_name( $show ) {
		$actions = array(
			'edit' => sprintf(
				'<a href="?page=%s&action=%s&show=%s">' . __( 'Edit', 'podlove' ) . '</a>',
				$_REQUEST[ 'page' ],
				'edit',
				$show->id
			),
			'delete' => sprintf(
				'<a href="?page=%s&action=%s&show=%s">' . __( 'Delete', 'podlove' ) . '</a>',
				$_REQUEST[ 'page' ],
				'delete',
				$show->id
			)
		);
	
		return sprintf('%1$s %2$s',
		    /*$1%s*/ $show->name,
		    /*$3%s*/ $this->row_actions( $actions )
		);
	}
	
	function column_id( $show ) {
		return $show->id;
	}
	
	function column_cover( $show ) {
		return $show->get_cover( '50' );
	}

	function column_feeds( $show ) {
		$feeds = $show->feeds();

		$out = '';

		foreach ( $feeds as $feed ) {
			$out .= '<div>';
			$out .= sprintf( '<strong>%s/%s: </strong>', $show->slug, $feed->slug );
			$out .= sprintf( '<a href="%s">%s</a>', $feed->get_subscribe_url(), __( 'Public Link', 'podlove' ) );
			if ( strlen( $feed->redirect_url ) > 0 ) {
				$out .= sprintf( ' | <a href="%s?redirect=no">%s</a>', $feed->get_subscribe_url(), __( 'Initial/Internal Feed', 'podlove' ) );
			}
			$out .= '</div>';
		}

		return $out;
	}

	function column_media( $show ) {
		return $show->media_file_base_uri;
	}

	function get_columns(){
		$columns = array(
			'id'    => 'ID',
			'cover' => 'Cover',
			'name'  => 'Name',
			'feeds' => 'Feeds',
			'media' => 'Media File Base URI'
		);
		return $columns;
	}
	
	function prepare_items() {
		// number of items per page
		$per_page = 10;
		
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		// retrieve data
		// TODO select data for current page only
		$data = \Podlove\Model\Show::all();
		
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
